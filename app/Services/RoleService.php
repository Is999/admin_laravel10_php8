<?php

namespace App\Services;

use App\Enum\Code;
use App\Enum\Delete;
use App\Enum\OrderBy;
use App\Enum\PermissionType;
use App\Enum\RedisKeys;
use App\Enum\RoleStatus;
use App\Exceptions\CustomizeException;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UserRoleAccess;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Arr;
use RedisException;

class RoleService extends Service
{

    /**
     * 存储角色权限信息
     * @param int $id
     * @param array $permissions
     * @return bool
     * @throws RedisException
     */
    public static function setRolePermissionCache(int $id, array $permissions): bool
    {
        return RedisService::loadSet(RedisKeys::ROLE_PERMISSION . $id, $permissions);
    }

    /**
     * 获取角色状态
     * @param int $id
     * @return mixed
     * @throws RedisException
     */
    public function getRoleStatus(int $id): mixed
    {
        // 获取角色当前状态
        $status = $this->getRoleStatusCache($id);
        if ($status === false) {
            // 查询数据表, 获取 permissions_id
            $status = Role::where([
                ['is_delete', Delete::NO]
                , ['id', $id]
            ])->value('status');

            // 写入缓存
            $this->setRoleStatusCache([$id => (int)$status]);
        }
        return $status;
    }

    /**
     * 获取权限module信息
     * @param string $roleId
     * @return mixed
     * @throws RedisException
     */
    public static function getRoleStatusCache(string $roleId): mixed
    {
        return RedisService::hGetTable(RedisKeys::ROLE_STATUS, $roleId);
    }

    /**
     * 存储角色status信息
     * @param array $roles
     * @return bool
     * @throws RedisException
     */
    public static function setRoleStatusCache(array $roles): bool
    {
        return RedisService::loadHash(RedisKeys::ROLE_STATUS, $roles);
    }

    /**
     * 获取角色权限
     * @param int $id 角色 传
     * @return array
     * @throws RedisException
     */
    public function getRolePermission(int $id): array
    {
        // 获取缓存角色权限
        return $this->getRolePermissionCache($id);
    }

    /**
     * 获取角色权限信息
     * @param int $id
     * @return array
     * @throws RedisException
     */
    public static function getRolePermissionCache(int $id): array
    {
        return RedisService::sMembersTable(RedisKeys::ROLE_PERMISSION . $id, true);
    }

    /**
     * 角色管理 上级角色下拉列表
     * @return array
     * @throws RedisException
     */
    public function roleTreeList(): array
    {
        // 从缓存中获取角色
        $roles = RedisService::getTable(RedisKeys::ROLE_TREE, true);
        return json_decode($roles, true);
    }

    /**
     * 列表,搜索
     * @param array $input 查询字段
     * @return array
     * @throws RedisException
     */
    public function roleList(array $input = []): array
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

        Role::with('children:id,pid')->where([
            ['is_delete', Delete::NO]
        ])->when($status !== null, function (Builder $query) use ($status) {
            return $query->where('status', $status);
        })->when($title, function (Builder $query, $val) {
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
     * @param int $adminId
     * @param array $input
     * @return bool
     * @throws CustomizeException
     * @throws RedisException
     */
    public function roleAdd(int $adminId, array $input): bool
    {
        $pid = Arr::get($input, 'pid', 0);

        $userService = new UserService;
        // 检查该用户是否有新增角色的权限
        if (!($userService->checkUserHasRole($adminId, $pid) || $userService->checkUserHasChildRole($adminId, $pid))) {
            throw new CustomizeException(Code::E100030);
        }

        // 获取上级族谱(pids) permissions_id
        $prole = Role::where([
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
        $exists = Role::where([
            ['is_delete', Delete::NO]
            , ['pid', $pid]
            , ['title', $title]
        ])->exists();

        if ($exists) {
            throw new CustomizeException(Code::E100023);
        }

        // 权限
        $parentPermissions = $prole->permissions_id ? explode(',', $prole->permissions_id) : [];
        if ($pid == Role::getSuperRole()) { // 继承超级管理员权限
            $parentPermissions = Permission::orderBy('id')->pluck('id')->toArray();
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
        $model = new Role;
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
     * @param int $adminId
     * @param int $id
     * @param array $input
     * @return bool
     * @throws CustomizeException
     * @throws RedisException
     */
    public function roleEdit(int $adminId, int $id, array $input): bool
    {
        // 超级管理员角色不能编辑
        if ($id == Role::getSuperRole()) {
            throw new CustomizeException(Code::E100025);
        }

        // 检查该用户是否有编辑角色的权限
        $userService = new UserService;
        if (!$userService->checkUserHasChildRole($adminId, $id)) {
            throw new CustomizeException(Code::E100031);
        }

        // 获取角色信息
        $model = Role::find($id);
        if (!$model) {
            throw new CustomizeException(Code::E100024);
        }

        // 角色名称
        $title = Arr::get($input, 'title');
        if ($title && $title != $model->title) {
            // 同级别不能存在相同角色
            $exists = Role::where([
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
                $permissions = $permissions ? Permission::whereIn('id', array_unique($permissions))
                    ->orderBy('id')->pluck('id')->toArray() : [];

                // 验证上级是否拥有该权限
                if ($permissions && $model->pid != Role::getSuperRole()) {
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
                $this->delRolePermissionsCache($model->id);
            }
        }

        // 状态
        $status = Arr::get($input, 'status');
        if ($status !== null && $model->status != $status) {

            // 重新赋值
            $model->status = $status;

            // 禁用处理
            if ($model->status != RoleStatus::ENABLED->value) {
                // 禁用所有下级角色
                $this->subRoleStatusDisabled($model->id);
            } else {
                // 判断上级状态
                if (!Role::where([
                    ['is_delete', Delete::NO]
                    , ['status', RoleStatus::ENABLED]
                    , ['id', $model->pid]
                ])->exists()) {
                    throw new CustomizeException(Code::E100028);
                }
            }

            // 删除该角色状态缓存
            $this->delRoleStatusCache([$model->id]);
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
            // 如果该角色拥有下级角色则不能删除
            $ids = Role::where([
                ['is_delete', Delete::NO]
                , ['pid', $model->id]
            ])->orderBy('id')->pluck('id')->toArray();
            if ($ids) {
                throw new CustomizeException(Code::E100026);
            }

            // 解除该角色与用户的关系
            $this->delUserRolesAccess($model->id);

            // 删除角色状态缓存
            $this->delRoleStatusCache([$model->id]);

            // 删除角色权限缓存
            $this->delRolePermissionsCache($model->id);

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
     * 验证上级是否拥有该权限
     * @param int $pid
     * @param mixed $permissions
     * @throws CustomizeException
     */
    private function checkParentPermission(int $pid, array $permissions): void
    {
        // 获取上级权限
        $permissionsIds = Role::where([
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
                $title = Permission::where('id', $v)->value('title');
                throw new CustomizeException(Code::E100029, ['permission' => $title]);
            }
        }
    }

    /**
     * 给所有下级角色添加权限
     * @param int $roleId
     * @param array $permissions
     * @throws RedisException
     */
    private function subRoleAddPermissions(int $roleId, array $permissions): void
    {
        Role::where([
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
            Role::where('id', $role->id)->update([
                'permissions_id' => implode(',', $permissionIds)
            ]);

            // 删除角色权限缓存
            $this->delRolePermissionsCache($role->id);
        });
    }

    /**
     * 删除角色信息
     * @param int $id
     * @return int
     * @throws RedisException
     */
    public static function delRolePermissionsCache(int $id): int
    {
        return self::redis()->del(RedisKeys::ROLE_PERMISSION . $id);
    }

    /**
     * 去掉所有下级角色的权限
     * @param int $roleId
     * @param array $permissions
     * @throws RedisException
     */
    private function subRoleDelPermissions(int $roleId, array $permissions): void
    {
        Role::where([
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
                Role::where('id', $role->id)->update([
                    'permissions_id' => $permissionIds ? implode(',', $permissionIds) : ''
                ]);

                // 删除角色权限缓存
                $this->delRolePermissionsCache($role->id);
            }
        });
    }

    /**
     * 禁用所有下级角色
     * @param int $roleId
     * @throws CustomizeException|RedisException
     */
    private function subRoleStatusDisabled(int $roleId): void
    {
        // 禁用所有下级角色
        $ids = Role::where([
            ['status', RoleStatus::ENABLED]
        ])->where(function (Builder $query) use ($roleId) {
            return $this->getPids($query, $roleId, true);
        })->orderBy('id')->pluck('id')->toArray();

        // 更新操作
        if ($ids) {
            // 删除角色状态缓存
            $this->delRoleStatusCache($ids);

            // 更新数据库
            $res = Role::whereIn('id', $ids)->update(['status' => RoleStatus::DISABLED]);
            // 下级角色状态更新失败
            if (!$res) {
                throw new CustomizeException(Code::E100032);
            }
        }
    }

    /**
     * 删除角色状态缓存
     * @param array $roleIds
     * @return int
     * @throws RedisException
     */
    public static function delRoleStatusCache(array $roleIds): int
    {
        return self::redis()->hDel(RedisKeys::ROLE_STATUS, ...$roleIds);
    }

    /**
     * 解除该角色与用户的关系
     * @param int $roleId
     * @throws CustomizeException|RedisException
     */
    private function delUserRolesAccess(int $roleId): void
    {
        // 删除用户和角色关系
        $uids = UserRoleAccess::where('role_id', $roleId)->pluck('user_id')->toArray();
        if ($uids) {
            $userService = new UserService;
            // 删除用户角色缓存
            foreach ($uids as $v) {
                $userService->delUserRoleCache($v);
            }

            // 删除表中数据
            $res = UserRoleAccess::where('role_id', $roleId)->delete();
            if (!$res) {
                throw new CustomizeException(Code::E100027);
            }
        }
    }

    /**
     * role.permission
     * @param int $adminId 管理员id
     * @param int $roleId 角色id
     * @param bool $isPid 验证上级是否有权限
     * @return array
     * @throws RedisException
     */
    public function rolePermission(int $adminId, int $roleId, bool $isPid): array
    {
        // 获取角色权限
        $rolePermissions = $this->getRolePermissionCache($roleId);

        // 是否可以编辑check box: false 不能选择，true 可以选择
        $isChecked = false;

        // 超级管理员id
        $superRoleId = Role::getSuperRole();

        // 获取角色是否超级管理员
        $isSuperRole = false;
        if (!$isPid && $superRoleId == $roleId) {
            $isSuperRole = true;
        }

        $userService = new UserService;
        // 当前用户是否是超级管理员
        $userIsSuperRole = $userService->checkUserIsSuperRole($adminId);
        // 超级管理员添加下级
        if ($isPid && $superRoleId == $roleId && $userIsSuperRole) {
            $isChecked = true;
        }

        // 获取父级权限
        $parentRolePermissions = $isPid ? $rolePermissions : [];
        if (!$isPid && !$isChecked) {
            $pid = Role::where('id', $roleId)->value('pid');
            if ($pid == Role::getSuperRole()) {
                $isChecked = true;
            } else {
                $parentRolePermissions = $this->getRolePermissionCache($pid);
            }
        }

        // 菜单图标
        $icons = Menu::select(['icon', 'permissions_uuid as uuid'])->pluck('icon', 'uuid')->toArray();

        $data = RedisService::getTable(RedisKeys::PERMISSION_TREE, true);
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
                'checked' => in_array($item['id'], $rolePermissions), // 当前角色拥的权限
                'disabled' => $isSuperRole || !($isChecked || $isParentHas), // 超级管理员权限禁止编辑
                'disableCheckbox' => !($isChecked || $isParentHas), // 上级没有得权限禁止选择
                'selectable' => $isChecked || $isParentHas, // 上级有得权限才可以选择
                'uuid' => $item['uuid'],
                'status' => $item['status'],
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
     * 角色数据转下拉框树数据
     * @param array $arr
     */
    public function roleToTree(array &$arr): void
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
