<?php

namespace App\Services;

use App\Enum\Code;
use App\Enum\Delete;
use App\Enum\LogChannel;
use App\Enum\MenuShortcut;
use App\Enum\MenuStatus;
use App\Enum\MenuType;
use App\Enum\OrderBy;
use App\Enum\PermissionType;
use App\Enum\RedisKeys;
use App\Enum\RoleStatus;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Models\Menus;
use App\Models\Permissions;
use App\Models\Roles;
use App\Models\UserRolesAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuthorizeService extends Service
{
    private static int $SUPER_ROLE = 1; // 超级管理员角色id
    private static int $ROLE_MANAGER = 2; // 管理者角色id


    /**
     * 超级管理员角色id
     * .env 配置 SUPER_ROLE
     * @return int
     */
    public function getSuperRole(): int
    {
        return env('SUPER_ROLE', self::$SUPER_ROLE);
    }


    /**
     * 管理者角色id
     * .env 配置 ROLE_MANAGER
     * @return int
     */
    public function getRoleManager(): int
    {
        return env('ROLE_MANAGER', self::$ROLE_MANAGER);
    }

    /**
     * @param int $uid 用户id
     * @param string $module 匹配模型
     * @return false
     */
    public function check(int $uid, string $module): bool
    {
        // 获取用户的角色
        $roles = $this->getUserRoles($uid);

        // 判断用户是否拥有超级管理员权限
        if (in_array($this->getSuperRole(), $roles)) {
            return true;
        }

        // 非超级管理员权限验证
        foreach ($roles as $roleId) {
            // 获取角色状态
            if ($this->getRoleStatus($roleId) != RoleStatus::ENABLED->value) {
                continue;
            }

            // 获取角色权限id
            $permissions = $this->getRolePermissions($roleId);

            // 获取权限module
            if ($permissions && in_array($module, $this->getPermissionsModule($permissions))) {
                // 匹配到权限
                return true;
            }
        }

        // 未匹配到权限
        return false;
    }

    /**
     * 获取用户角色 更新
     * @param int $uid
     * @param bool $renew
     * @return array
     */
    public function getUserRoles(int $uid, bool $renew = false): array
    {
        // 从缓存中获取用户角色
        $roles = $renew ? [] : RedisService::getUserRoles($uid);

        // 刷新缓存
        if (empty($roles) && ($renew || !RedisService::checkUserRolesExists($uid))) {
            // 查询数据表, 获取 role_id 集合
            $roles = DB::table((new UserRolesAccess)->tableName('u'))->join((new Roles())->tableName('r'), 'u.role_id', '=', 'r.id')
                ->where('u.user_id', $uid)
                ->where('r.status', RoleStatus::ENABLED)
                ->where('r.is_delete', Delete::NO)
                ->orderBy('r.id')
                ->pluck('r.id')
                ->toArray();

            // 删除缓存
            if ($renew) RedisService::delUserRoles($uid);

            // 写入缓存
            RedisService::setUserRoles($uid, $roles);
        }

        return $roles;
    }

    /**
     * 获取角色状态
     * @param int $id
     * @return mixed
     */
    public function getRoleStatus(int $id): mixed
    {
        // 获取角色当前状态
        $status = RedisService::getRoleStatus($id);
        if ($status === false) {
            // 查询数据表, 获取 permissions_id
            $status = Roles::where([
                ['is_delete', Delete::NO]
                , ['id', $id]
            ])->value('status');

            // 写入缓存
            RedisService::setRolesStatus([$id => (int)$status]);
        }
        return $status;
    }

    /**
     * 获取角色权限
     * @param int $id 角色 传
     * @return array
     */
    public function getRolePermissions(int $id): array
    {
        // 获取缓存角色权限
        return RedisService::getRolePermissions($id);
    }

    /**
     * 获取权限列表modules
     * @param array $permissionIds
     * @return array
     */
    public function getPermissionsModule(array $permissionIds): array
    {
        $modules = []; // 找到的module
        $list = []; // 缓存中未找到的权限

        // 缓存中获取权限module
        foreach (RedisService::getPermissionsModule($permissionIds) as $k => $v) {
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
            $data = Permissions::whereIn('id', array_keys($list))->get(['id', 'module']);

            // 重新赋值
            foreach ($data as $v) {
                $list[$v->id] = $v->module; // 放置缓存的数据

                // 过滤module
                if (!($v === '' || $v === '-' || is_numeric($v))) {
                    $modules[] = $v->module; // 添加到返回信息
                }
            }

            //写入缓存
            RedisService::setPermissionsModule($list);
        }

        return $modules;
    }

    /**
     * 获取权限列表uuid
     * @param array $permissionIds
     * @return array
     */
    public function getPermissionsUuid(array $permissionIds): array
    {
        return RedisService::getPermissionsUuid($permissionIds);
    }

    /**
     * 角色管理权限
     * @param int $uid
     * @param int $roleId
     * @param bool $isProleId $roleId 是否是父级id
     * @param bool $checkRoleManager
     * @return bool
     */
    public function checkUserRoleManager(int $uid, int $roleId, bool $isProleId = false, bool $checkRoleManager = true): bool
    {
        // 获取用户的角色
        $roles = $this->getUserRoles($uid);

        // 判断用户是否拥有超级管理员权限
        if (in_array($this->getSuperRole(), $roles)) {
            return true;
        }

        // 判断用户是否拥有角色管理者权限
        if ($checkRoleManager && in_array($this->getRoleManager(), $roles)) {
            return true;
        }

        // 判断父级id
        if ($isProleId) {
            // 该用户拥有的角色内是否拥有该pid
            if (in_array($roleId, $roles) && $this->getRoleStatus($roleId) == RoleStatus::ENABLED->value) {
                return true;
            }
        }

        // 查角色族谱是否拥有该用户拥有的角色id
        $pids = Roles::where('id', $roleId)->value('pids');
        if ($pids) {
            $pids = explode(',', $pids);
            foreach ($roles as $role) {
                if (in_array($role, $pids)) {
                    return true;
                }
            }
        }

        // 未匹配到权限
        return false;
    }

    /**
     * 角色管理权限
     * @param int $uid
     * @param int $roleId
     * @return bool
     */
    public function checkUserHasChildRole(int $uid, int $roleId): bool
    {
        // 获取用户的角色
        $roles = $this->getUserRoles($uid);

        // 查角色族谱是否拥有该用户拥有的角色id
        $pids = Roles::where('id', $roleId)->value('pids');
        if ($pids) {
            $pids = explode(',', $pids);
            foreach ($roles as $role) {
                if (in_array($role, $pids)) {
                    return true;
                }
            }
        }

        // 未匹配到权限
        return false;
    }

    /**
     * 获取用户权限uuid
     * @param int $uid
     * @return array
     */
    public function getUserPermissionUuid(int $uid): array
    {
        $data = [
            'superUserRole' => 0, // 是否是超级管理员
            'roles' => [], // 角色
            'permissions' => [] // 权限
        ];

        // 获取用户的角色
        $roles = $this->getUserRoles($uid);

        $data['roles'] = $roles;

        // 判断用户是否拥有超级管理员权限
        if (in_array($this->getSuperRole(), $roles)) {
            $data['superUserRole'] = 1;
            return $data;
        }

        $permissions = [];
        // 非超级管理员权限
        foreach ($roles as $roleId) {
            // 获取角色状态
            if ($this->getRoleStatus($roleId) != RoleStatus::ENABLED->value) {
                continue;
            }

            // 获取角色权限id
            $permissions = array_merge($permissions, $this->getRolePermissions($roleId));
        }

        // 去重, 并获取uuid
        if ($permissions) {
            $data['permissions'] = $this->getPermissionsUuid(array_unique($permissions,SORT_NUMERIC));
        }

        return $data;
    }

    /**
     * 获取导航栏
     * @param Request $request
     * @param int $uid
     * @return array
     */
    public function getMenuNav(Request $request, int $uid): array
    {
        $nav = [];

        // 从缓存中获取导航栏
        $menu = RedisService::getTable(RedisKeys::MENUS_TREE, true);

        if ($menu) {
            $nav = json_decode($menu, true);
            $permission = $this->getUserPermissionUuid($uid);

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
     * @param Request $request
     * @return array
     */
    public function menuTreeList(Request $request): array
    {
        $nav = [];

        // 从缓存中获取导航栏
        $menu = RedisService::getTable(RedisKeys::MENUS_TREE, true);

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
     * 权限数据转下拉框树数据
     * @param array $arr
     */
    public function permissionsToTree(array &$arr): void
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

    /**
     * 角色数据转下拉框树数据
     * @param array $arr
     */
    public function rolesToTree(array &$arr): void
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

    /**
     * 账号管理 编辑|新增角色下拉列表
     * @param Request $request
     * @return array
     */
    public function userRoleTreeList(Request $request): array
    {
        // 从缓存中获取角色
        $data = RedisService::getTable(RedisKeys::ROLE_TREE, true);
        if ($data) {
            $data = json_decode($data, true);

            // 获取用户角色
            $roles = $this->getUserRoles($request->offsetGet('user.id'));
            $isSuperRole = false;
            if (in_array($this->getSuperRole(), $roles)) {
                $isSuperRole = true;
            }

            // 过滤禁用的角色
            self::arrayWalkRecursive($data, function (&$arr, $key, $item) use ($isSuperRole, &$roles) {
                if ($item['status'] !== RoleStatus::ENABLED->value || $item['is_delete'] != Delete::NO->value) {
                    unset($arr[$key]);
                } else {
                    // 将子级角色添加到角色列表中
                    if (isset($item['children']) && in_array($item['pid'], $roles) && !in_array($item['id'], $roles)) {
                        $roles[] = $item['id'];
                    }

                    $disabled = $isSuperRole || in_array($item['pid'], $roles);

                    $arr[$key]['disabled'] = !$disabled; // 禁止编辑
                    $arr[$key]['disableCheckbox'] = !$disabled; // 上级有得角色才可以编辑
                    $arr[$key]['selectable'] = $disabled; // 上级有得权限才可以编辑
                }
            }, 'children');

            return $data;
        }
        return [];
    }

    /**
     * 用户角色id
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function userRoles(Request $request, int $id): array
    {
        // 获取账号角色
        return UserRolesAccess::where('user_id', $id)->pluck('role_id')->toArray();
    }

    /**
     * 角色管理 上级角色下拉列表
     * @param Request $request
     * @return array
     */
    public function roleTreeList(Request $request): array
    {
        // 从缓存中获取角色
        $roles = RedisService::getTable(RedisKeys::ROLE_TREE, true);
        return json_decode($roles, true);
    }

    /**
     * 列表,搜索
     * @param Request $request
     * @param array $input 查询字段
     * @return array
     */
    public function roleIndex(Request $request, array $input = []): array
    {
        $status = Arr::get($input, 'status'); // 状态
        $title = Arr::get($input, 'title'); // 角色名称
        $pid = Arr::get($input, 'pid'); // 父级Id
        $cache = Arr::get($input, 'cache', true); // 查缓存

        if ($cache) {
            // 从缓存中获取角色
            $roles = RedisService::getTable(RedisKeys::ROLE_TREE, true);
            $list = json_decode($roles, true);
            return ['total' => count($list), 'items' => $list];
        }

        if ($pid === null) $pid = ($title !== null) ? $pid : 0; // pid 处理

        $isGenealogy = Arr::get($input, 'is_genealogy', 0); // 0 直属, 1 家谱
        $orderByField = Arr::get($input, 'field', 'id'); // 排序字段
        $orderByType = OrderBy::getLabel(Arr::get($input, 'order')); // 排序方式

        $list = [];

        Roles::with('children:id,pid')->where([
            ['is_delete', Delete::NO]
        ])->when($status !== null, function ($query) use ($status) {
            return $query->where('status', $status);
        })->when($title, function ($query, $val) {
            return $query->where('title', $val);
        })->when($pid !== null, function (Builder $query) use ($isGenealogy, $pid) {
            return self::getPids($query, $pid, $isGenealogy);
        })->orderBy($orderByField, $orderByType)->orderBy('id') // 排序
        ->select([
            'id', 'title', 'pid', 'pids', 'status', 'permissions_id'
            , 'describe', 'created_at', 'updated_at'
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
     * 添加角色
     * @param Request $request
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function roleAdd(Request $request, array $input): bool
    {
        $pid = Arr::get($input, 'pid', 0);

        // 检查该用户是否有新增角色的权限
        if (!$this->checkUserRoleManager($request->offsetGet('user.id'), $pid, true)) {
            throw new CustomizeException(Code::E100030);
        }

        // 获取上级族谱(pids) permissions_id
        $prole = Roles::where([
            ['is_delete', Delete::NO]
            , ['status', RoleStatus::ENABLED]
            , ['id', $pid]
        ])->first([
            'title', 'pids', 'permissions_id'
        ]);

        // 验证上级是否存在
        if (null === $prole) {
            throw new CustomizeException(Code::E100022);
        }

        // 拼装 pids
        $pids = $prole->pids ? $prole->pids . ',' . $pid : $pid;

        // 同级别不能存在相同角色
        $title = Arr::get($input, 'title');
        $exists = Roles::where([
            ['is_delete', Delete::NO]
            , ['pid', $pid]
            , ['title', $title]
        ])->exists();

        if ($exists) {
            throw new CustomizeException(Code::E100023);
        }

        // 权限
        $parentPermissions = $prole->permissions_id ? explode(',', $prole->permissions_id) : [];
        if ($pid == $this->getSuperRole()) { // 继承超级管理员权限
            $parentPermissions = Permissions::orderBy('id')->pluck('id')->toArray();
        }

        // 权限
        $permissions = Arr::get($input, 'permissions');
        if ($permissions && is_array($permissions)) {
            // 排序, 并比较
            asort($permissions);
            // 本次新增权限
            $intersect = array_intersect($permissions, $parentPermissions);
            $permissions = implode(',', $intersect);
        } else {
            $permissions = '';
        }

        // 新增角色
        $model = new Roles;
        $model->title = $title; // 角色名称
        $model->pid = $pid; // 父级ID
        $model->pids = $pids; // 父级ID(族谱)
        $model->status = RoleStatus::ENABLED; //状态：1正常
        $model->permissions_id = $permissions; // 拥有的权限id
        $model->describe = Arr::get($input, 'describe', $prole->title . ' / ' . $title); // 描述
        $model->is_delete = Delete::NO; // 是否删除
        $model->created_at = date('Y-m-d H:i:s'); // 创建时间

        $res = $model->save();
        if ($res) {
            // 刷新 校色下拉框 String role_select_tree
            RedisService::initTable(RedisKeys::ROLE_TREE);
        }
        return $res;
    }

    /**
     * role.edit
     * 需要刷新角色权限缓存:1.状态禁用, 2.重新赋值权限, 3.删除角色
     * @param Request $request
     * @param int $id
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function roleEdit(Request $request, int $id, array $input): bool
    {
        // 超级管理员角色不能编辑
        if ($id == $this->getSuperRole()) {
            throw new CustomizeException(Code::E100025);
        }

        // 检查该用户是否有编辑角色的权限
        if (!$this->checkUserRoleManager($request->offsetGet('user.id'), $id, false, false)) {
            throw new CustomizeException(Code::E100031);
        }

        // 获取角色信息
        $model = Roles::find($id);
        if (!$model) {
            throw new CustomizeException(Code::E100024);
        }

        // 角色名称
        $title = Arr::get($input, 'title');
        if ($title && $title != $model->title) {
            // 同级别不能存在相同角色
            $exists = Roles::where([
                ['is_delete', Delete::NO]
                , ['pid', $model->pid]
                , ['title', $title]
            ])->exists();
            if ($exists) {
                throw new CustomizeException(Code::E100023);
            }

            // 重新赋值
            $model->title = $title;
        }

        // 权限
        $permissions = Arr::get($input, 'permissions');
        if ($permissions && is_array($permissions)) {
            // 排序, 并比较
            asort($permissions);
            if (implode(',', $permissions) != $model->permissions_id) {
                // 先从权限表过滤下所有存在的权限
                $permissions = $permissions ? Permissions::whereIn('id', array_unique($permissions))
                    ->orderBy('id')->pluck('id')->toArray() : [];

                // 验证上级是否拥有该权限
                if ($permissions && $model->pid != self::getSuperRole()) {
                    self::checkParentPermission($model->pid, $permissions);
                }

                $oldPermissions = $model->permissions_id ? explode(',', $model->permissions_id) : [];

                // 该角色的下级角色去掉取消的权限
                $isExtends = Arr::get($input, 'is_extends');
                if ($permissions && $isExtends) {
                    // 本次新增权限
                    $addPermissions = array_diff($permissions, $oldPermissions);
                    if ($addPermissions) {
                        // 所有下级角色新增本次新增权限
                        $this->subRoleAddPermissions($model->id, $addPermissions);
                    }
                }

                // 本次取消权限
                $delPermissions = array_diff($oldPermissions, $permissions);
                if ($delPermissions) {
                    // 所有下级角色删除本次取消权限
                    $this->subRoleDelPermissions($model->id, $delPermissions);
                }

                // 重新赋值
                $model->permissions_id = $permissions ? implode(',', $permissions) : '';

                // 删除角色权限缓存
                RedisService::delRolePermissions($model->id);
            }
        }

        // 状态
        $status = Arr::get($input, 'status');
        if ($status !== null && $model->status != $status) {
            // 管理者角色不能更改状态
            if ($id == $this->getRoleManager() && !in_array($this->getSuperRole(), $this->getUserRoles($request->offsetGet('user.id')))) {
                throw new CustomizeException(Code::E100033);
            }

            // 重新赋值
            $model->status = $status;

            // 禁用处理
            if ($model->status !== RoleStatus::ENABLED->value) {
                // 禁用所有下级角色
                $this->subRoleStatusDisabled($model->id);
            } else {
                // 判断上级状态
                if (!Roles::where([
                    ['is_delete', Delete::NO]
                    , ['status', RoleStatus::ENABLED]
                    , ['id', $model->pid]
                ])->exists()) {
                    throw new CustomizeException(Code::E100028);
                }
            }

            // 删除该角色状态缓存
            RedisService::delRolesStatus([$model->id]);
        }

        // 描述
        $describe = Arr::get($input, 'describe');
        if ($describe !== null) {
            // 重新赋值
            $model->describe = $describe;
        }

        // 删除
        $isDelete = Arr::get($input, 'is_delete');
        if ($isDelete && $model->is_delete != $isDelete) {
            // 管理者角色不能删除
            if ($id == $this->getRoleManager()) {
                throw new CustomizeException(Code::E100033);
            }

            // 如果该角色拥有下级角色则不能删除
            $ids = Roles::where([
                ['is_delete', Delete::NO]
                , ['pid', $model->id]
            ])->orderBy('id')->pluck('id')->toArray();
            if ($ids) {
                throw new CustomizeException(Code::E100026);
            }

            // 解除该角色与用户的关系
            $this->delUserRolesAccess($model->id);

            // 删除角色状态缓存
            RedisService::delRolesStatus([$model->id]);

            // 删除角色权限缓存
            RedisService::delRolePermissions($model->id);

            // 重新赋值
            $model->is_delete = $isDelete;
        }

        // 更新时间
        $model->updated_at = date('Y-m-d H:i:s');

        // 更新角色权限
        $res = $model->save();
        if ($res) {
            // 刷新 角色下拉框 String role_select_tree
            RedisService::initTable(RedisKeys::ROLE_TREE);
        }
        return $res;
    }

    /**
     * 给所有下级角色添加权限
     * @param int $roleId
     * @param array $permissions
     */
    private function subRoleAddPermissions(int $roleId, array $permissions): void
    {
        Roles::where([
            ['is_delete', Delete::NO]
            , ['status', RoleStatus::ENABLED]
        ])->where(function (Builder $query) use ($roleId) {
            // 族谱
            return $this->getPids($query, $roleId, true);
        })->select([
            'id', 'permissions_id'
        ])->orderBy('id')->lazyById()->each(function ($role) use ($permissions) {
            // 计算新值
            $permissionIds = $role->permissions_id ? explode(',', $role->permissions_id) : [];
            $permissionIds = array_unique(array_merge($permissionIds, $permissions)); // 合并去重
            sort($permissionIds, SORT_NUMERIC); // 重新排序

            // 更新操作
            Roles::where('id', $role->id)->update([
                'permissions_id' => implode(',', $permissionIds)
            ]);

            // 删除角色权限缓存
            RedisService::delRolePermissions($role->id);
        });
    }

    /**
     * 去掉所有下级角色的权限
     * @param int $roleId
     * @param array $permissions
     */
    private function subRoleDelPermissions(int $roleId, array $permissions): void
    {
        Roles::where([
            ['is_delete', Delete::NO]
        ])->where(function (Builder $query) use ($roleId) {
            // 族谱
            return $this->getPids($query, $roleId, true);
        })->select([
            'id', 'permissions_id'
        ])->orderBy('id')->lazyById()->each(function ($role) use ($permissions) {
            if ($role->permissions_id) {
                // 计算新值
                $permissionIds = array_values(array_diff(explode(',', $role->permissions_id), $permissions));

                // 更新操作
                Roles::where('id', $role->id)->update([
                    'permissions_id' => $permissionIds ? implode(',', $permissionIds) : ''
                ]);

                // 删除角色权限缓存
                RedisService::delRolePermissions($role->id);
            }
        });
    }

    /*
     * 禁用所有下级角色
     * @param int $roleId
     * @throws CustomizeException
     */
    private function subRoleStatusDisabled(int $roleId): void
    {
        // 禁用所有下级角色
        $ids = Roles::where([
            ['status', RoleStatus::ENABLED]
        ])->where(function (Builder $query) use ($roleId) {
            return $this->getPids($query, $roleId, true);
        })->orderBy('id')->pluck('id')->toArray();

        // 更新操作
        if ($ids) {
            // 删除角色状态缓存
            RedisService::delRolesStatus($ids);

            // 更新数据库
            $res = Roles::whereIn('id', $ids)->update(['status' => RoleStatus::DISABLED]);
            // 下级角色状态更新失败
            if (!$res) {
                throw new CustomizeException(Code::E100032);
            }
        }
    }

    /**
     * 解除该角色与用户的关系
     * @param int $roleId
     * @throws CustomizeException
     */
    private function delUserRolesAccess(int $roleId): void
    {
        // 删除用户和角色关系
        $uids = UserRolesAccess::where('role_id', $roleId)->pluck('user_id')->toArray();
        if ($uids) {
            // 删除用户角色缓存
            foreach ($uids as $v) {
                RedisService::delUserRoles($v);
            }

            // 删除表中数据
            $res = UserRolesAccess::where('role_id', $roleId)->delete();
            if (!$res) {
                throw new CustomizeException(Code::E100027);
            }
        }
    }

    /**
     * 验证上级是否拥有该权限
     * @param int $pid
     * @param mixed $permissions
     * @throws CustomizeException
     */
    private function checkParentPermission(int $pid, array $permissions): void
    {
        // 获取上级权限
        $permissionsIds = Roles::where([
            ['is_delete', Delete::NO]
            , ['status', RoleStatus::ENABLED]
            , ['id', $pid]
        ])->value('permissions_id');
        if ($permissionsIds === false) {
            throw new CustomizeException(Code::E100028);
        }

        // 匹配权限
        $permissionsIds = $permissionsIds ? explode(',', $permissionsIds) : [];
        foreach ($permissions as $v) {
            if (!in_array($v, $permissionsIds)) {
                $title = Permissions::where('id', $v)->value('title');
                throw new CustomizeException(Code::E100029, ['permission' => $title]);
            }
        }
    }

    /**
     * role.permission
     * @param Request $request
     * @param int $id
     * @param bool $isPid 验证上级是否有权限
     * @return array
     */
    public function permission(Request $request, int $id, bool $isPid): array
    {
        // 获取角色权限
        $rolePermissions = RedisService::getRolePermissions($id);

        // 是否可以编辑check box: false 不能选择，true 可以选择
        $isChecked = false;

        // 超级管理员id
        $superRoleId = $this->getSuperRole();

        // 获取角色是否超级管理员
        $isSuperRole = false;
        if (!$isPid && $superRoleId == $id) {
            $isSuperRole = true;
        }

        // 超级管理员添加下级
        if ($isPid && $superRoleId == $id && $superRoleId == $request->offsetGet('user.id')) {
            $isChecked = true;
        }

        // 获取父级权限
        $parentRolePermissions = $isPid ? $rolePermissions : [];
        if (!$isPid && !$isChecked) {
            $pid = Roles::where('id', $id)->value('pid');
            $parentRolePermissions = RedisService::getRolePermissions($pid);
        }

        // 菜单图标
        $icons = Menus::select(['icon', 'permissions_uuid as uuid'])->pluck('icon', 'uuid')->toArray();

        $data = RedisService::getTable(RedisKeys::PERMISSIONS_TREE, true);
        $list = json_decode($data, true);
        $typeMap = PermissionType::titleMap();

        // 根据前端处理返回数据
        $this->arrayWalkRecursive($list, function (&$arr, $key, $item) use ($rolePermissions, $parentRolePermissions, $isChecked, $icons, $typeMap, $isSuperRole) {
            $typeTitle = $typeMap[$item['type']] ?? '其它';
            $isParentHas = in_array($item['id'], $parentRolePermissions); // 上级拥的权限
            $value = [
                'id' => $item['id'],
                'title' => $item['title'] . ' (' . $typeTitle . ')',
                'icon' => $icons[$item['uuid']] ?? '',
                // 'checked' => $isSuperRole || in_array($item['id'], $rolePermissions), // 当前角色拥的权限
                'disabled' => $isSuperRole, // 超级管理员权限禁止编辑
                'disableCheckbox' => !($isChecked || $isParentHas), // 上级没有得权限禁止选择
                // 'selectable' => ($isChecked || $isParentHas), // 上级有得权限才可以选择
                'uuid' => $item['uuid'],
                'describe' => $item['describe'],
                'module' => $item['module'],
            ];
            if (isset($item['children']) && $item['children']) {
                $value['children'] = $item['children'];
            }

            $arr[$key] = $value;
        }, 'children');

        return $list;
    }

    /**
     * menu.menuPermissionUuid
     * @param Request $request
     * @return array
     */
    public function menuPermissionUuid(Request $request): array
    {
        $permissions = Menus::pluck('permissions_uuid')->toArray();
        $data = RedisService::getTable(RedisKeys::PERMISSIONS_TREE, true);
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

    /**
     * permission.parentPermissionTreeList
     * @param Request $request
     * @return array
     */
    public function permissionTreeList(Request $request): array
    {
        $data = RedisService::getTable(RedisKeys::PERMISSIONS_TREE, true);
        return json_decode($data, true);
    }

    /**
     * permission.index
     * @param Request $request
     * @param array $input
     * @return array
     */
    public function permissionList(Request $request, array $input): array
    {
        $uuid = Arr::get($input, 'uuid'); // 唯一标识
        $title = Arr::get($input, 'title'); // 权限名称
        $module = Arr::get($input, 'module'); // 权限匹配模型
        $pid = Arr::get($input, 'pid'); // 父级Id
        $type = Arr::get($input, 'type'); // 类型
        $cache = Arr::get($input, 'cache', true); // 查缓存

        if ($cache) {
            // 从缓存中获取角色
            $roles = RedisService::getTable(RedisKeys::PERMISSIONS_TREE, true);
            $list = json_decode($roles, true);
            return ['total' => count($list), 'items' => $list];
        }

        if ($pid === null) $pid = ($uuid !== null || $title !== null || $module !== null || $type !== null) ? $pid : 0; // pid 处理

        $isGenealogy = Arr::get($input, 'is_genealogy', 0); // 0 直属, 1 家谱
        $orderByField = Arr::get($input, 'field', 'id'); // 排序字段
        $orderByType = OrderBy::getLabel(Arr::get($input, 'order')); // 排序方式

        // 返回数据
        $list = [];

        Permissions::when($type === null, function ($query) {
            return $query->with('children:id,pid');
        })->when($uuid, function ($query, $val) {
            return $query->where('uuid', $val);
        })->when($title, function ($query, $val) {
            return $query->where('title', $val);
        })->when($module, function ($query, $val) {
            return $query->where('module', $val);
        })->when($type !== null, function ($query) use ($type) {
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
            , 'describe', 'created_at', 'updated_at'
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
     * 获取家谱中包含该pid的记录
     * @param Builder $query ;
     * @param int $pid 上级id
     * @param bool $isGenealogy true 查整个家谱包含 pid 的记录，false 查pid等于pid 的记录
     * @return mixed
     */
    private function getPids(Builder $query, int $pid, bool $isGenealogy = false): Builder
    {
        // 族谱
        if ($pid && $isGenealogy) {
            return $query->whereRaw('FIND_IN_SET(?, pids)', [$pid]);
        } elseif (!$pid && $isGenealogy) {
            return $query;
        } else {
            // 直属
            return $query->where('pid', $pid);
        }
    }

    /**
     * 从数据库获取最大id,生成uuid标识（8位）
     * @return string
     */
    public function getMaxUuid(): string
    {
        $prefix = base_convert(rand(10, 35), 10, 36); // a-z
        $suffix = base_convert(rand(0, 35), 10, 36); // 0-z
        $middle = base_convert(Permissions::max('id'), 10, 36);
        // 不够 6位填充0
        $length = strlen($middle);
        if ($length < 6) {
            $middle .= str_repeat('0', 6 - $length);
        }

        return $prefix . $middle . $suffix;
    }

    /**
     * 添加权限
     * @param Request $request
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function permissionAdd(Request $request, array $input): bool
    {
        // uuid 唯一
        $uuid = Arr::get($input, 'uuid');
        $exists = Permissions::where([
            ['uuid', $uuid]
        ])->exists();

        if ($exists) {
            throw new CustomizeException(Code::E100034, compact('uuid'));
        }

        // module 是string时验证是否唯一
        $module = Arr::get($input, 'module', PermissionType::OTHERS->value);
        if (!$module) $module = '';
        if ($module && !is_numeric($module)) {
            $exists = Permissions::where([
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
            $pids = Permissions::where([
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
        $model = new Permissions;
        $model->uuid = $uuid; // 唯一标识
        $model->title = Arr::get($input, 'title', '未知'); // 权限名称
        $model->module = $module; // 权限匹配模型(路由名称 | 控制器/方法)
        $model->pid = $pid; // 父级ID
        $model->pids = $pids; // 父级ID(族谱)
        $model->type = Arr::get($input, 'type', PermissionType::OTHERS->value); //状态：1正常
        $model->describe = Arr::get($input, 'describe', $model->title); // 描述
        $model->created_at = date('Y-m-d H:i:s'); // 创建时间

        $res = $model->save();
        if ($res) {
            // 刷新 权限 Hash permissions_module
            if (!is_numeric($model->module)) RedisService::setPermissionsModule([$model->id => $model->module]);

            // 刷新 权限 Hash permissions_uuid
            RedisService::setPermissionsUuid([$model->id => $model->uuid]);

            // 刷新 权限下拉框 String permissions_select_tree
            RedisService::initTable(RedisKeys::PERMISSIONS_TREE);
        }
        return $res;
    }

    /**
     * permission.edit
     * @param Request $request
     * @param int $id
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function permissionEdit(Request $request, int $id, array $input): bool
    {
        // 获取要编辑的权限
        $model = Permissions::find($id);
        if (!$model) {
            throw new CustomizeException(Code::E100037);
        }

        // module 是string时验证是否唯一
        $module = Arr::get($input, 'module');
        $oldModule = $model->module;
        if ($module && $oldModule != $module) {
            if (!is_numeric($module)) {
                $exists = Permissions::where([
                    ['module', $module]
                ])->exists();

                if ($exists) {
                    throw new CustomizeException(Code::E100035, compact('module'));
                }
            }

            // 删除 权限 Hash permissions_module
            RedisService::DelPermissionsModule($model->id);
            // 重新赋值
            $model->module = $module; // 权限匹配模型(路由名称 | 控制器/方法)
        }

        $model->title = Arr::get($input, 'title', $model->title); // 权限名称
        $model->type = Arr::get($input, 'type', $model->type);  //状态：1正常
        $model->describe = Arr::get($input, 'describe', $model->describe); // 描述
        $model->updated_at = date('Y-m-d H:i:s'); // 创建时间

        // 更新
        $res = $model->save();
        if ($res) {
            // 刷新 权限 Hash permissions_module
            if ($module && $oldModule != $module) {
                if (!is_numeric($module)) {
                    RedisService::setPermissionsModule([$model->id => $model->module]);
                }
            }
            // 刷新 权限下拉框 String permissions_select_tree
            RedisService::initTable(RedisKeys::PERMISSIONS_TREE);
        }
        return $res;
    }

    /**
     * permission.del
     * @param Request $request
     * @param int $id
     * @return bool
     * @throws CustomizeException
     */
    public function permissionDel(Request $request, int $id): bool
    {
        $model = Permissions::find($id);
        if (!$model) {
            return true;
        }

        // 经绑定到菜单的权限不能删除
        $title = Menus::where('permissions_uuid', $model->uuid)->value('title');
        if ($title !== null) {
            throw new CustomizeException(Code::E100039, compact('title'));
        }

        // 绑定到角色的权限不能删除
        $title = Roles::when($id, function ($query, $id) {
            return $query->whereRaw('FIND_IN_SET(?, permissions_id)', [$id]);
        })->value('title');
        if ($title !== null) {
            throw new CustomizeException(Code::E100038, compact('title'));
        }

        // 更新
        $res = $model->delete();
        if ($res) {
            // 删除 权限 Hash permissions_module
            RedisService::delPermissionsModule($model->id);
            // 删除 权限 Hash permissions_uuid
            RedisService::delPermissionsUuid($model->id);
            // 刷新 权限下拉框 String permissions_select_tree
            RedisService::initTable(RedisKeys::PERMISSIONS_TREE);
        }
        return $res;
    }

    /**
     * menu.index
     * @param Request $request
     * @param array $input
     * @return array
     */
    public function menuIndex(Request $request, array $input): array
    {
        // 查缓存
        if (Arr::get($input, 'cache', true)) {
            // 从缓存中获取角色
            $roles = RedisService::getTable(RedisKeys::MENUS_TREE, true);
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
        //Menus::with('parent:id,title')->when($uuid, function ($query, $val) { // 获取父级
        Menus::when($uuid, function ($query, $val) { // uuid
            return $query->where('permissions_uuid', $val);
        })->when($title, function ($query, $val) { // 菜单名称
            return $query->where('title', $val);
        })->when($status !== null, function ($query) use ($status) { // 状态
            return $query->where('status', $status);
        })->when($isShortcut !== null, function ($query) use ($isShortcut) { // 快捷
            return $query->where('is_shortcut', $isShortcut);
        })->when($pid !== null, function (Builder $query) use ($isGenealogy, $pid) { // pid
            return $this->getPids($query, $pid, $isGenealogy);
        })->orderBy($orderByField, $orderByType)->orderBy('id') // 排序
        ->select([ // 查询字段
            'id', 'permissions_uuid', 'title', 'status', 'pid', 'pids', 'path', 'component'
            , 'title_lang', 'icon', 'sort', 'type', 'is_shortcut'
            , 'describe', 'created_at', 'updated_at'
        ])->lazyById()->each(function ($menu) use (&$list, $isShortcut) { // 获取子级
            if ($isShortcut === null && Menus::where('pid', $menu->id)->exists()) {
                $menu->children = [];
            }
            $list[] = $menu;
        });

        return ['total' => count($list), 'items' => $list];
    }

    /**
     * menu.add
     * @param Request $request
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function menuAdd(Request $request, array $input): bool
    {
        // 获取权限uuid
        $uuid = Arr::get($input, 'permissions_uuid');
        if (!Permissions::where([
            ['uuid', $uuid]
        ])->exists()) {
            throw new CustomizeException(Code::E100037);
        }

        // 验证uuid 是否已存在
        $title = Menus::where([
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
            $pids = Menus::where([
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
        $model = new Menus;
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
        $model->sort = $input['sort'] ?: (int)Menus::max('sort') + 1; // 排序
        $model->is_shortcut = Arr::get($input, 'is_shortcut', MenuShortcut::NO); // 是否快捷: 1 是, 0 否
        $model->describe = Arr::get($input, 'describe', $model->title); // 描述
        $model->created_at = date('Y-m-d H:i:s'); // 创建时间

        $res = $model->save();
        if ($res && $model->status) {
            // 刷新 菜单 String menus_info
            RedisService::initTable(RedisKeys::MENUS_TREE);
        }
        return $res;
    }

    /**
     * menu.edit
     * @param Request $request
     * @param int $id
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function menuEdit(Request $request, int $id, array $input): bool
    {
        // 获取要编辑的菜单
        $model = Menus::find($id);
        if (!$model) {
            throw new CustomizeException(Code::E100042);
        }

        // 获取权限uuid
        $uuid = Arr::get($input, 'permissions_uuid');
        if ($uuid && $uuid != $model->permissions_uuid) {
            if (!Permissions::where([
                ['uuid', $uuid]
            ])->exists()) {
                throw new CustomizeException(Code::E100037);
            }
            // 验证uuid 是否已存在
            $title = Menus::where([
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
                $pids = Menus::where([
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
            RedisService::initTable(RedisKeys::MENUS_TREE);
        }
        return $res;
    }

    /**
     * user.roleList
     * @param Request $request
     * @param int $uid
     * @return array
     */
    public function userRoleList(Request $request, int $uid): array
    {
        $admin = $request->offsetGet('user.id');
        $list = [];
        DB::table((new UserRolesAccess)->getTable(), 'a')
            ->join((new Roles)->tableName('r'), 'a.role_id', 'r.id')
            ->where('a.user_id', $uid)
            ->select(['a.*', 'r.title'])
            ->orderBy('role_id')
            ->lazy()->each(function ($role) use ($admin, &$list) {
                $role->isUpdate = $this->checkUserRoleManager($admin, $role->role_id);
                $list[] = $role;
            });
        return $list;
    }

    /**
     * user.editRoles
     * @param Request $request
     * @param int $uid
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function userEditRoles(Request $request, int $uid, array $input): bool
    {
        $roleIds = Arr::get($input, 'roles');
        if (!$roleIds || !is_array($roleIds)) {
            throw new CustomizeException(Code::E100049);
        }
        // 过滤出正常的角色
        $idArr = Roles::where([['status', RoleStatus::ENABLED], ['is_delete', Delete::NO]])->whereIn('id', $roleIds)->pluck('id')->toArray();
        if (!$idArr) {
            throw new CustomizeException(Code::E100044);
        }

        $idArr2 = UserRolesAccess::where([['user_id', $uid]])->pluck('role_id')->toArray();

        // 计算要删除的数据
        $delArr = array_diff($idArr2, $idArr);
        // 验证是否有权限删除该记录
        if ($delArr) {
            foreach ($delArr as $id) {
                if (!$this->checkUserRoleManager($request->offsetGet('user.id'), $id)) {
                    $title = Roles::where('id', $id)->value('title');
                    throw new CustomizeException(Code::E100050, compact('title'));
                }
            }

        }

        // 计算要新增的数据
        $insertArr = array_diff($idArr, $idArr2);
        // 验证是否有权限添加该记录
        if ($insertArr) {
            foreach ($insertArr as $id) {
                if (!$this->checkUserRoleManager($request->offsetGet('user.id'), $id)) {
                    $title = Roles::where('id', $id)->value('title');
                    throw new CustomizeException(Code::E100045, compact('title'));
                }
            }
        }

        // 验证完毕 数据入库
        DB::beginTransaction();
        try {
            // 删除记录
            if ($delArr) {
                $res = DB::table((new UserRolesAccess)->tableName())->where('user_id', $uid)->whereIn('role_id', $delArr)->delete();
                if (!$res) {
                    throw new CustomizeException(Code::E100051);
                }
            }

            // 新增数据
            if ($insertArr) {
                $insertData = [];
                foreach ($insertArr as $id) {
                    $insertData[] = ['user_id' => $uid, 'role_id' => $id, 'created_at' => date('Y-m-d H:i:s')];
                }
                $res = DB::table((new UserRolesAccess)->tableName())->insert($insertData);
                if (!$res) {
                    throw new CustomizeException(Code::E100052);
                }
            }

            // 刷新角色缓存(删除后获取角色缓存数据时自动刷新)
            RedisService::delUserRoles($uid);

            // 提交事务
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Logger::error(LogChannel::DEV, __METHOD__, compact('uid', 'delArr', 'insertArr', $e));
            throw new CustomizeException($e->getCode(), $e->getMessage());
        }

        return true;
    }

    /**
     * user.addRole
     * @param Request $request
     * @param int $uid
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function userAddRole(Request $request, int $uid, array $input): bool
    {
        $roleId = Arr::get($input, 'roleId', 0);
        $title = Roles::where([['id', $roleId], ['status', RoleStatus::ENABLED], ['is_delete', Delete::NO]])->value('title');
        if ($title == null) {
            throw new CustomizeException(Code::E100044);
        }

        if (UserRolesAccess::where([['role_id', $roleId], ['user_id', $uid]])->exists()) {
            return true;
        }

        if (!$this->checkUserRoleManager($request->offsetGet('user.id'), $roleId)) {
            throw new CustomizeException(Code::E100045, compact('title'));
        }

        $model = new UserRolesAccess;
        $model->user_id = $uid;
        $model->role_id = $roleId;
        $model->created_at = date('Y-m-d H:i:s'); // 创建时间

        $res = $model->save();
        if ($res) {
            // 刷新角色缓存(删除后获取角色缓存数据时自动刷新)
            RedisService::delUserRoles($uid);
        }
        return $res;
    }

    /**
     * user.delRole
     * @param Request $request
     * @param int $uid
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function userDelRole(Request $request, int $uid, array $input): bool
    {
        $roleId = Arr::get($input, 'user_roles_id', 0);

        // 查找用户与角色关联记录
        $model = UserRolesAccess::find($roleId);
        if (!$model) {
            return true;
        }

        // 验证是否有权限删除该记录
        if (!$this->checkUserRoleManager($request->offsetGet('user.id'), $model->role_id)) {
            throw new CustomizeException(Code::E100046);
        }

        // 删除用户和角色关系
        $res = $model->delete();
        if ($res) {
            // 刷新角色缓存(删除后获取角色缓存数据时自动刷新)
            RedisService::delUserRoles($uid);
        }
        return $res;
    }

    /**
     * 相当于array_walk_recursive, 可自定义递归字段
     * @param array $arr
     * @param callable $callback 返回true 终止
     * @param $child string 子集名称
     * @return array
     */
    private function arrayWalkRecursive(array &$arr, callable $callback, string $child = ''): array
    {
        array_walk($arr, function (&$item, $key) use (&$arr, $callback, $child) {
            $done = $callback($arr, $key, $item);
            if (!$done && $child && isset($arr[$key][$child]) && is_array($arr[$key][$child])) {
                $this->arrayWalkRecursive($arr[$key][$child], $callback, $child);
            }
        });

        // 重新排序
        $arr = array_values($arr);
        return $arr;
    }


}