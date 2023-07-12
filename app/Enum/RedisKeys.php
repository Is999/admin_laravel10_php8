<?php

namespace App\Enum;

/**
 *  Redis数据key
 */
enum RedisKeys: string
{
    // 前缀
    const DELIMIT = ':'; // key 分割符
    const TABLE = 'table' . self::DELIMIT; // 表数据缓存前缀标识

    // TABLE 数据缓存 keys start
    const ROLES_STATUS = 'roles_status'; // 角色状态 Hash table:roles_status
    const ROLES_PERMISSIONS = 'roles_permissions' . self::DELIMIT; // 角色权限 Set table:roles_permissions:{role.id}
    const PERMISSIONS_MODULE = 'permissions_module'; // 权限 Hash table:permissions_module
    const PERMISSIONS_UUID = 'permissions_uuid'; // 权限 Hash table:permissions_uuid
    const MENUS_TREE = 'menus_tree'; // 菜单 String table:menus_tree
    const PERMISSIONS_TREE = 'permissions_tree'; // 权限 String table:permissions_tree
    const ROLE_TREE = 'role_tree'; // 角色 String  table:role_tree
    const CONFIG_UUID = 'config_uuid' . self::DELIMIT; // 参数配置 Hash table:config_uuid:{config.uuid}

    // TABLE 数据缓存 keys end

    const TOKEN = 'token' . self::DELIMIT; // 用户token String token:{user.id}
    const USERINFO = 'userinfo' . self::DELIMIT; // 用户信息 Hash userinfo:{user.id}
    const USER_ROLES = 'user_roles' . self::DELIMIT; // 用户权限 Set user_roles:{user.id}


}