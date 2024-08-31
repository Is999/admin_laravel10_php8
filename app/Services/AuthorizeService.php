<?php

namespace App\Services;

use App\Enum\RoleStatus;
use App\Models\Role;
use RedisException;

class AuthorizeService extends Service
{
    /**
     * 验证路由
     * @param int $uid 用户id
     * @param string $module 匹配模型
     * @return false
     * @throws RedisException
     */
    public function check(int $uid, string $module): bool
    {
        $userService = new UserService;
        // 获取用户的角色
        $roles = $userService->getUserRole($uid);

        // 判断用户是否拥有超级管理员权限
        if (in_array(Role::getSuperRole(), $roles)) {
            return true;
        }

        // 非超级管理员权限验证
        $permissionsService = new PermissionService;
        $rolesService = new RoleService;
        foreach ($roles as $roleId) {
            // 获取角色状态
            if ($rolesService->getRoleStatus($roleId) != RoleStatus::ENABLED->value) {
                continue;
            }

            // 获取角色权限id
            $permissions = $rolesService->getRolePermission($roleId);

            // 获取权限module
            if ($permissions && in_array($module, $permissionsService->getPermissionModule($permissions))) {
                // 匹配到权限
                return true;
            }
        }

        // 未匹配到权限
        return false;
    }
}
