<?php /** @noinspection ALL */

namespace App\Services;

use App\Enum\Code;
use App\Enum\OrderBy;
use App\Enum\PermissionStatus;
use App\Enum\PermissionType;
use App\Enum\RedisKeys;
use App\Exceptions\CustomizeException;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Arr;
use RedisException;

class PermissionService extends Service
{
    /**
     * 获取权限列表modules
     * @param array $permissionIds
     * @return array
     * @throws RedisException
     */
    public function getPermissionModule(array $permissionIds): array
    {
        $modules = []; // 找到的module
        $list = []; // 缓存中未找到的权限

        // 缓存中获取权限module
        foreach ($this->getPermissionModuleCache($permissionIds) as $k => $v) {
            if ($v === false) {
                $list[$permissionIds[$k]] = '-'; // 将缓存未找到的数据值用"-"占位, 避免重复查询数据库
                continue;
            }

            // 过滤module
            if (!($v === '' || $v === '-' || is_numeric($v))) {
                $modules[] = $v;
            }
        }

        // 查询数据库并刷新缓存
        if ($list) {
            // 查询数据, 获取id, module
            $data = Permission::whereIn('id', array_keys($list))->where('status', 1)->get(['id', 'module']);

            // 重新赋值
            foreach ($data as $v) {
                $list[$v->id] = $v->module; // 放置缓存的数据

                // 过滤module
                if (!($v === '' || $v === '-' || is_numeric($v))) {
                    $modules[] = $v->module; // 添加到返回信息
                }
            }

            //写入缓存
            $this->setPermissionModuleCache($list);
        }

        return $modules;
    }

    /**
     * 获取权限module信息
     * @param array $permissionIds
     * @return array
     * @throws RedisException
     */
    public static function getPermissionModuleCache(array $permissionIds): array
    {
        return RedisService::hMGetTable(RedisKeys::PERMISSION_MODULE, $permissionIds, true);
    }

    /**
     * 存储权限module信息
     * @param array $permissions
     * @return bool
     * @throws RedisException
     */
    public function setPermissionModuleCache(array $permissions): bool
    {
        return RedisService::loadHash(RedisKeys::PERMISSION_MODULE, $permissions);
    }

    /**
     * 获取权限列表uuid
     * @param array $permissionIds
     * @return array
     * @throws RedisException
     */
    public function getPermissionUuid(array $permissionIds): array
    {
        $uuids = []; // 找到的uuid
        $list = []; // 缓存中未找到的权限

        // 缓存中获取权限module
        foreach ($this->getPermissionUuidCache($permissionIds) as $k => $v) {
            if ($v === false) {
                $list[$permissionIds[$k]] = '-'; // 将缓存未找到的数据值用"-"占位, 避免重复查询数据库
            } else {
                $uuids[] = $v;
            }
        }

        // 查询数据库并刷新缓存
        if ($list) {
            // 查询数据, 获取id, uuid
            $data = Permission::whereIn('id', array_keys($list))->where('status', 1)->get(['id', 'uuid']);

            // 重新赋值
            foreach ($data as $v) {
                $list[$v->id] = $v->uuid; // 放置缓存的数据
                $uuids[] = $v->uuid;
            }

            //写入缓存
            $this->setPermissionUuidCache($list);
        }

        return $uuids;
    }

    /**
     * 获取权限uuid信息
     * @param array $permissionIds
     * @return array
     * @throws RedisException
     */
    public static function getPermissionUuidCache(array $permissionIds): array
    {
        return RedisService::hMGetTable(RedisKeys::PERMISSION_UUID, $permissionIds, true);
    }

    /**
     * 存储权限uuid信息
     * @param array $permissions
     * @return bool
     * @throws RedisException
     */
    public static function setPermissionUuidCache(array $permissions): bool
    {
        return RedisService::loadHash(RedisKeys::PERMISSION_UUID, $permissions);
    }

    /**
     * permission.parentPermissionTreeList
     * @return array
     * @throws RedisException
     */
    public function permissionTreeList(): array
    {
        $data = RedisService::getTable(RedisKeys::PERMISSION_TREE, true);
        return json_decode($data, true);
    }

    /**
     * permission.index
     * @param array $input
     * @return array
     * @throws RedisException
     */
    public function permissionList(array $input): array
    {
        $uuid = Arr::get($input, 'uuid'); // 唯一标识
        $title = Arr::get($input, 'title'); // 权限名称
        $module = Arr::get($input, 'module'); // 权限匹配模型
        $pid = Arr::get($input, 'pid'); // 父级Id
        $type = Arr::get($input, 'type'); // 类型
        $status = Arr::get($input, 'status'); // 状态
        $cache = Arr::get($input, 'cache', true); // 查缓存

        if ($cache) {
            // 从缓存中获取角色
            $roles = RedisService::getTable(RedisKeys::PERMISSION_TREE, true);
            $list = json_decode($roles, true);
            return ['total' => count($list), 'items' => $list];
        }

        if ($pid === null) $pid = ($uuid !== null || $title !== null || $module !== null || $type !== null) ? $pid : 0; // pid 处理

        $isGenealogy = Arr::get($input, 'is_genealogy', 0); // 0 直属, 1 家谱
        $orderByField = Arr::get($input, 'field', 'id'); // 排序字段
        $orderByType = OrderBy::getLabel(Arr::get($input, 'order')); // 排序方式

        // 返回数据
        $list = [];

        Permission::when($type === null, function (Builder $query) {
            return $query->with('children:id,pid');
        })->when($uuid, function (Builder $query, $val) {
            return $query->where('uuid', $val);
        })->when($title, function (Builder $query, $val) {
            return $query->where('title', $val);
        })->when($module, function (Builder $query, $val) {
            return $query->where('module', $val);
        })->when($status !== null, function (Builder $query) use ($status) { // 状态
            return $query->where('status', $status);
        })->when($type !== null, function (Builder $query) use ($type) {
            if (is_array($type)) {
                if (count($type) > 1) {
                    return $query->whereIn('type', $type);
                } elseif (count($type) == 1) {
                    $type = array_shift($type);
                } else {
                    return $query;
                }
            }
            return $query->where('type', $type);
        })->when($pid !== null, function (Builder $query) use ($isGenealogy, $pid) {
            return $this->getPids($query, $pid, $isGenealogy);
        })->orderBy($orderByField, $orderByType)->orderBy('id') // 排序
        ->select([
            'id', 'uuid', 'title', 'module', 'pid', 'pids', 'type'
            , 'status', 'describe', 'created_at', 'updated_at'
        ])->lazyById()->each(function ($permission) use (&$list) { // 获取子级
            $permission = $permission->toArray();
            if (isset($permission['children']) && $permission['children']) {
                $permission['children'] = [];
            } else {
                $permission['children'] = null;
            }
            $list[] = $permission;
        });

        return ['total' => count($list), 'items' => $list];
    }

    /**
     * 从数据库获取最大id,生成uuid标识（8位）
     * @return string
     */
    public function getMaxUuid(): string
    {
        $prefix = base_convert(rand(10, 35), 10, 36); // a-z
        $suffix = base_convert(rand(0, 35), 10, 36); // 0-z
        $middle = base_convert(Permission::max('id'), 10, 36);
        // 不够 6位填充0
        $length = strlen($middle);
        if ($length < 6) {
            $middle .= str_repeat('0', 6 - $length);
        }

        return $prefix . $middle . $suffix;
    }

    /**
     * 添加权限
     * @param array $input
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function permissionAdd(array $input): bool
    {
        // uuid 唯一
        $uuid = Arr::get($input, 'uuid');
        $exists = Permission::where([
            ['uuid', $uuid]
        ])->exists();

        if ($exists) {
            throw new CustomizeException(Code::E100034, compact('uuid'));
        }

        // module 是string时验证是否唯一
        $module = Arr::get($input, 'module', PermissionType::OTHERS->value);
        if (!$module) $module = '';
        if ($module && !is_numeric($module)) {
            $exists = Permission::where([
                ['module', $module]
            ])->exists();

            if ($exists) {
                throw new CustomizeException(Code::E100035, compact('module'));
            }
        }

        // 验证上级并获取族谱
        $pid = Arr::get($input, 'pid', 0); // 上级id
        $pids = ''; // 族谱
        if ($pid) {
            // 获取上级族谱(pids) permissions_id
            $pids = Permission::where([
                ['id', $pid]
            ])->value('pids');

            // 验证上级是否存在
            if (null === $pids) {
                throw new CustomizeException(Code::E100036);
            }
        }

        // 拼装 pids
        $pids = $pids ? $pids . ',' . $pid : ($pid ?: '');


        // 新增角色
        $model = new Permission;
        $model->uuid = $uuid; // 唯一标识
        $model->title = Arr::get($input, 'title', '未知'); // 权限名称
        $model->module = $module; // 权限匹配模型(路由名称 | 控制器/方法)
        $model->pid = $pid; // 父级ID
        $model->pids = $pids; // 父级ID(族谱)
        $model->type = Arr::get($input, 'type', PermissionType::OTHERS->value); //类型：类型: 0查看, 1新增, 2修改, 3删除, 4目录, 5菜单, 6页面, 7按钮, 8其它
        $model->status = Arr::get($input, 'status', PermissionStatus::ENABLED->value);  //状态：1 启用；0 禁用
        $model->describe = Arr::get($input, 'describe', $model->title); // 描述
        $model->created_at = date('Y-m-d H:i:s'); // 创建时间

        $res = $model->save();
        if ($res) {
            // 状态启用才存入缓存
            if ($model->status == PermissionStatus::ENABLED->value) {
                // 刷新 权限 Hash permissions_module
                if (!is_numeric($model->module)) $this->setPermissionModuleCache([$model->id => $model->module]);

                // 刷新 权限 Hash permissions_uuid
                $this->setPermissionUuidCache([$model->id => $model->uuid]);
            }

            // 刷新 权限下拉框 String permissions_select_tree
            RedisService::initTable(RedisKeys::PERMISSION_TREE);
        }
        return $res;
    }

    /**
     * permission.edit
     * @param int $id
     * @param array $input
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function permissionEdit(int $id, array $input): bool
    {
        // 获取要编辑的权限
        $model = Permission::find($id);
        if (!$model) {
            throw new CustomizeException(Code::E100037);
        }

        // module 是string时验证是否唯一
        $module = Arr::get($input, 'module');
        $oldModule = $model->module;
        if ($module && $oldModule != $module) {
            if (!is_numeric($module)) {
                $exists = Permission::where([
                    ['module', $module]
                ])->exists();

                if ($exists) {
                    throw new CustomizeException(Code::E100035, compact('module'));
                }
            }

            // 删除 权限 Hash permissions_module
            $this->delPermissionModuleCache($model->id);
            // 重新赋值
            $model->module = $module; // 权限匹配模型(路由名称 | 控制器/方法)
        }

        $oldStatus = $model->status;

        $model->title = Arr::get($input, 'title', $model->title); // 权限名称
        $model->type = Arr::get($input, 'type', $model->type);  //类型：类型: 0查看, 1新增, 2修改, 3删除, 4目录, 5菜单, 6页面, 7按钮, 8其它
        $model->status = Arr::get($input, 'status', $model->status);  //状态：1 启用；0 禁用
        $model->describe = Arr::get($input, 'describe', $model->describe); // 描述
        $model->updated_at = date('Y-m-d H:i:s'); // 创建时间

        // 更新
        $res = $model->save();
        if ($res) {
            // 刷新 权限 Hash permissions_module
            if ($module && $oldModule != $module) {
                if (!is_numeric($module)) {
                    $this->setPermissionModuleCache([$model->id => $model->module]);
                }
            }

            // 禁用状态删除缓存
            if ($oldStatus != $model->status) {
                if ($model->status == PermissionStatus::DISABLED->value) {
                    // 删除 权限 Hash permissions_module
                    $this->delPermissionModuleCache($model->id);
                    // 删除 权限 Hash permissions_uuid
                    $this->delPermissionUuidCache($model->id);
                } else {
                    // 刷新 权限 Hash permissions_module
                    if (!is_numeric($model->module)) $this->setPermissionModuleCache([$model->id => $model->module]);

                    // 刷新 权限 Hash permissions_uuid
                    $this->setPermissionUuidCache([$model->id => $model->uuid]);
                }
            }

            // 刷新 权限下拉框 String permissions_select_tree
            RedisService::initTable(RedisKeys::PERMISSION_TREE);
        }
        return $res;
    }

    /**
     * 删除权限module信息
     * @param int $permissionIds
     * @return int
     * @throws RedisException
     */
    public static function delPermissionModuleCache(int $permissionIds): int
    {
        return self::redis()->hDel(RedisKeys::PERMISSION_MODULE, $permissionIds);
    }

    /**
     * 删除权限uuid信息
     * @param int $permissionIds
     * @return int
     * @throws RedisException
     */
    public static function delPermissionUuidCache(int $permissionIds): int
    {
        return self::redis()->hDel(RedisKeys::PERMISSION_UUID, $permissionIds);
    }

    /**
     * permission.del
     * @param int $id
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function permissionDel(int $id): bool
    {
        $model = Permission::find($id);
        if (!$model) {
            return true;
        }

        // 经绑定到菜单的权限不能删除
        $title = Menu::where('permissions_uuid', $model->uuid)->value('title');
        if ($title !== null) {
            throw new CustomizeException(Code::E100039, compact('title'));
        }

        // 绑定到角色的权限不能删除
        $title = Role::when($id, function (Builder $query, $id) {
            return $query->whereRaw('FIND_IN_SET(?, permissions_id)', [$id]);
        })->value('title');
        if ($title !== null) {
            throw new CustomizeException(Code::E100038, compact('title'));
        }

        // 更新
        $res = $model->delete();
        if ($res) {
            // 删除 权限 Hash permissions_module
            $this->delPermissionModuleCache($model->id);
            // 删除 权限 Hash permissions_uuid
            $this->delPermissionUuidCache($model->id);
            // 刷新 权限下拉框 String permissions_select_tree
            RedisService::initTable(RedisKeys::PERMISSION_TREE);
        }
        return $res;
    }

    /**
     * 权限数据转下拉框树数据
     * @param array $arr
     */
    public function permissionToTree(array &$arr): void
    {
        $arr = $this->arrayWalkRecursive($arr, function (&$arr, $key, $item) {
            if ($item['child_recursion']) {
                $arr[$key]['children'] = $item['child_recursion'];
            }

            // 删除 child_recursion
            if (isset($arr[$key]['child_recursion'])) {
                unset($arr[$key]['child_recursion']);
            }
        }, 'children');
    }

}
