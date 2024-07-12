<?php

namespace App\Services;

use App\Enum\Code;
use App\Enum\MenuShortcut;
use App\Enum\MenuStatus;
use App\Enum\MenuType;
use App\Enum\OrderBy;
use App\Enum\RedisKeys;
use App\Exceptions\CustomizeException;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Arr;
use RedisException;

class MenuService extends Service
{
    /**
     * 获取导航栏
     * @param int $uid
     * @return array
     * @throws RedisException
     */
    public function getMenuNav(int $uid): array
    {
        $nav = [];

        // 从缓存中获取导航栏
        $menu = RedisService::getTable(RedisKeys::MENU_TREE, true);

        if ($menu) {
            $nav = json_decode($menu, true);
            $permission = (new UserService)->getUserPermissionUuid($uid);

            $permissions = $permission['permissions']; // 权限
            $this->arrayWalkRecursive($nav, function (&$arr, $key, $item) use ($permission, $permissions) {
                // 过滤菜单: 1.隐藏的， 2 非超级管理员并且没权限的
                if ($item['status'] != MenuStatus::SHOW->value || (!$permission['superUserRole'] && !in_array($item['permissions_uuid'], $permissions))) {
                    unset($arr[$key]);
                    return;
                }

                // 前端 mata参数
                $mata = [
                    'id' => $item['id'],
                    'pid' => $item['pid'],
                    'title' => $item['title_lang'],
                    'defaultTitle' => $item['title'],
                    'icon' => $item['icon'],
                ];

                // 重新赋值
                $arr[$key] = [
                    'path' => $item['path'],
                    'name' => $item['permissions_uuid'],
                    'component' => $item['component'] ?: 'LAYOUT',
                    'meta' => $mata,
                ];

                // 子路由
                if (isset($item['children']) && $item['children']) {
                    $arr[$key]['children'] = $item['children'];
                }

            }, 'children');
        }

        return $nav;
    }

    /**
     * 菜单下拉框
     * @return array
     * @throws RedisException
     */
    public function menuTreeList(): array
    {
        $nav = [];

        // 从缓存中获取导航栏
        $menu = RedisService::getTable(RedisKeys::MENU_TREE, true);

        if ($menu) {
            $nav = json_decode($menu, true);

            $this->arrayWalkRecursive($nav, function (&$arr, $key, $item) {
                $value = [
                    'value' => $item['id'],
                    'title' => $item['title'],
                ];
                if (isset($item['children']) && $item['children']) {
                    $value['children'] = $item['children'];
                }
                $arr[$key] = $value;
            }, 'children');
        }

        return $nav;
    }

    /**
     * 菜单数据转Vue菜单接口数据
     * @param array $arr
     */
    public function menusToTree(array &$arr): void
    {
        $arr = $this->arrayWalkRecursive($arr, function (&$arr, $key, $item) {
            // 子路由
            if ($item['child_recursion']) {
                $arr[$key]['children'] = $item['child_recursion'];
            }

            // 删除 child_recursion
            if (isset($arr[$key]['child_recursion'])) {
                unset($arr[$key]['child_recursion']);
            }
        }, 'children');
    }


    /**
     * menu.index
     * @param array $input
     * @return array
     * @throws RedisException
     */
    public function menuList(array $input): array
    {
        // 查缓存
        if (Arr::get($input, 'cache', true)) {
            // 从缓存中获取角色
            $roles = RedisService::getTable(RedisKeys::MENU_TREE, true);
            $list = json_decode($roles, true);
            return ['total' => count($list), 'items' => $list];
        }

        // 查询条件
        $uuid = Arr::get($input, 'permissions_uuid'); // 唯一标识
        $title = Arr::get($input, 'title'); // 菜单名称
        $status = Arr::get($input, 'status'); // 状态：1正常,  0隐藏
        $isShortcut = Arr::get($input, 'is_shortcut'); // 是否快捷

        $pid = Arr::get($input, 'pid'); // 父级Id
        if ($pid === null) $pid = ($uuid !== null || $title !== null || $isShortcut !== null) ? $pid : 0; // pid 处理

        $isGenealogy = Arr::get($input, 'is_genealogy', 0); // 0 直属, 1 家谱
        $orderByField = Arr::get($input, 'field', 'sort'); // 排序字段
        $orderByType = OrderBy::getLabel(Arr::get($input, 'order')); // 排序方式

        // 返回数据
        $list = [];

        // 查询数据
        //Menus::with('parent:id,title')->when($uuid, function (Builder $query, $val) { // 获取父级
        Menu::when($uuid, function (Builder $query, $val) { // uuid
            return $query->where('permissions_uuid', $val);
        })->when($title, function (Builder $query, $val) { // 菜单名称
            return $query->where('title', $val);
        })->when($status !== null, function (Builder $query) use ($status) { // 状态
            return $query->where('status', $status);
        })->when($isShortcut !== null, function (Builder $query) use ($isShortcut) { // 快捷
            return $query->where('is_shortcut', $isShortcut);
        })->when($pid !== null, function (Builder $query) use ($isGenealogy, $pid) { // pid
            return $this->getPids($query, $pid, $isGenealogy);
        })->orderBy($orderByField, $orderByType)->orderBy('id') // 排序
        ->select([ // 查询字段
            'id', 'permissions_uuid', 'title', 'status', 'pid', 'pids', 'path', 'component'
            , 'title_lang', 'icon', 'sort', 'type', 'is_shortcut'
            , 'describe', 'created_at', 'updated_at'
        ])->lazyById()->each(function ($menu) use (&$list, $isShortcut) { // 获取子级
            if ($isShortcut === null && Menu::where('pid', $menu->id)->exists()) {
                $menu->children = [];
            }
            $list[] = $menu;
        });

        return ['total' => count($list), 'items' => $list];
    }

    /**
     * menu.add
     * @param array $input
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function menuAdd(array $input): bool
    {
        // 获取权限uuid
        $uuid = Arr::get($input, 'permissions_uuid');
        if (!Permission::where([
            ['uuid', $uuid]
        ])->exists()) {
            throw new CustomizeException(Code::E100037);
        }

        // 验证uuid 是否已存在
        $title = Menu::where([
            ['permissions_uuid', $uuid]
        ])->value('title');
        if ($title) {
            throw new CustomizeException(Code::E100040, compact('title'));
        }


        // 验证上级并获取族谱
        $pid = Arr::get($input, 'pid', 0); // 上级id
        $pids = ''; // 族谱
        if ($pid) {
            // 获取上级族谱(pids) permissions_id
            $pids = Menu::where([
                ['id', $pid]
            ])->value('pids');

            // 验证上级是否存在
            if (null === $pids) {
                throw new CustomizeException(Code::E100041);
            }
        }

        // 拼装 pids
        $pids = $pids ? $pids . ',' . $pid : ($pid ?: '');


        // 新增角色
        $model = new Menu;
        $model->permissions_uuid = $uuid; // 唯一标识
        $model->title = Arr::get($input, 'title', '未知'); // 菜单名称
        $model->title_lang = Arr::get($input, 'title_lang', $model->title); // 菜单名称
        $model->status = Arr::get($input, 'status', MenuStatus::HIDE); // 状态: 1 显示, 0 隐藏
        $model->type = Arr::get($input, 'type', MenuType::DIRS); // 类型: 1 菜单, 0 目录
        $model->pid = $pid; // 父级ID
        $model->pids = $pids; // 父级ID(族谱)
        $model->path = Arr::get($input, 'path', ''); // 路由地址
        $model->component = Arr::get($input, 'component', $model->type == MenuType::DIRS->value ? 'LAYOUT' : ''); // 组件地址
        $model->icon = Arr::get($input, 'icon', ''); // 图标
        $model->sort = $input['sort'] ?: (int)Menu::max('sort') + 1; // 排序
        $model->is_shortcut = Arr::get($input, 'is_shortcut', MenuShortcut::NO); // 是否快捷: 1 是, 0 否
        $model->describe = Arr::get($input, 'describe', $model->title); // 描述
        $model->created_at = date('Y-m-d H:i:s'); // 创建时间

        $res = $model->save();
        if ($res && $model->status) {
            // 刷新 菜单 String menus_info
            RedisService::initTable(RedisKeys::MENU_TREE);
        }
        return $res;
    }

    /**
     * menu.edit
     * @param int $id
     * @param array $input
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function menuEdit(int $id, array $input): bool
    {
        // 获取要编辑的菜单
        $model = Menu::find($id);
        if (!$model) {
            throw new CustomizeException(Code::E100042);
        }

        // 获取权限uuid
        $uuid = Arr::get($input, 'permissions_uuid');
        if ($uuid && $uuid != $model->permissions_uuid) {
            if (!Permission::where([
                ['uuid', $uuid]
            ])->exists()) {
                throw new CustomizeException(Code::E100037);
            }
            // 验证uuid 是否已存在
            $title = Menu::where([
                ['permissions_uuid', $uuid]
            ])->whereNot('id', $id)->value('title');
            if ($title) {
                throw new CustomizeException(Code::E100040, compact('title'));
            }
            $model->permissions_uuid = $uuid; // 权限标识
        }


        // 验证上级并获取族谱
        $pid = Arr::get($input, 'pid'); // 上级id
        if ($pid !== null && $pid != $model->id && $pid != $model->pid) {
            // 获取上级族谱(pids) permissions_id
            $pids = '';
            if ($pid) {
                $pids = Menu::where([
                    ['id', $pid]
                ])->value('pids');

                // 验证上级是否存在
                if (null === $pids) {
                    throw new CustomizeException(Code::E100041);
                }
            }

            // 拼装 pids
            $pids = $pids ? $pids . ',' . $pid : ($pid ?: '');

            // 重新赋值
            $model->pid = $pid;
            $model->pids = $pids;
        }

        $model->type = Arr::get($input, 'type', $model->type); //类型
        $model->title = Arr::get($input, 'title', $model->title); // 菜单名称
        $model->title_lang = Arr::get($input, 'title_lang', $model->title_lang); // 菜单名称
        $model->status = Arr::get($input, 'status', $model->status); // 状态: 1显示, 0隐藏
        $model->path = Arr::get($input, 'path', $model->path); //路由地址
        $model->component = Arr::get($input, 'component', $model->type == MenuType::DIRS->value ? 'LAYOUT' : $model->component); //组件地址
        $model->icon = Arr::get($input, 'icon', $model->icon); //图标
        $model->sort = Arr::get($input, 'sort', $model->sort); //排序
        $model->is_shortcut = Arr::get($input, 'is_shortcut', $model->is_shortcut); // 是否快捷: 1是, 0否
        $model->describe = Arr::get($input, 'describe', $model->describe); // 描述
        $model->updated_at = date('Y-m-d H:i:s'); // 创建时间
        // 更新
        $res = $model->save();
        if ($res) {
            // 刷新 菜单 String menus_info
            RedisService::initTable(RedisKeys::MENU_TREE);
        }
        return $res;
    }


    /**
     * menu.menuPermissionUuid
     * @return array
     * @throws RedisException
     */
    public function menuPermissionUuid(): array
    {
        $permissions = Menu::pluck('permissions_uuid')->toArray();
        $data = RedisService::getTable(RedisKeys::PERMISSION_TREE, true);
        $list = json_decode($data, true);
        $this->arrayWalkRecursive($list, function (&$arr, $key, $item) use ($permissions) {
            if (!in_array($item['type'], [4, 5])) {
                unset($arr[$key]);
                return;
            }
            $value = [
                'value' => $item['uuid'],
                'title' => $item['title'] . ' (' . $item['uuid'] . ')',
                'disabled' => in_array($item['uuid'], $permissions),
            ];
            if (isset($item['children']) && $item['children']) {
                $value['children'] = $item['children'];
            }

            $arr[$key] = $value;
        }, 'children');

        return $list;
    }
}
