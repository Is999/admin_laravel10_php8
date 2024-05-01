<?php

namespace App\Enum;

/**
 *  Redis数据key
 */
enum RedisKeys: string
{
    const DELIMIT = ':'; // key 分割符

    // TABLE 数据缓存 keys start
    const ROLES_STATUS = 'roles_status'; // 角色状态 Hash roles_status
    const ROLES_PERMISSIONS = 'roles_permissions' . self::DELIMIT; // 角色权限 Set roles_permissions:{role.id}
    const PERMISSIONS_MODULE = 'permissions_module'; // 权限 Hash permissions_module
    const PERMISSIONS_UUID = 'permissions_uuid'; // 权限 Hash permissions_uuid
    const MENUS_TREE = 'menus_tree'; // 菜单 String menus_tree
    const PERMISSIONS_TREE = 'permissions_tree'; // 权限 String permissions_tree
    const ROLE_TREE = 'role_tree'; // 角色 String  role_tree
    const CONFIG_UUID = 'config_uuid' . self::DELIMIT; // 参数配置 Hash config_uuid:{config.uuid}
    const SECRET_KEY_AES = 'secret_key_aes' . self::DELIMIT; // AES key Hash secret_key_aes:{secret_key.uuid}
    const SECRET_KEY_RSA = 'secret_key_rsa' . self::DELIMIT; // AES key Hash secret_key_rsa:{secret_key.uuid}

    // TABLE 数据缓存 keys end

    const ADMIN_TOKEN = 'admin_token' . self::DELIMIT; // 用户token String admin_token:{user.id}
    const ADMIN_USERINFO = 'admin_userinfo' . self::DELIMIT; // 用户信息 Hash admin_userinfo:{user.id}
    const ADMIN_USER_ROLES = 'admin_user_roles' . self::DELIMIT; // 用户权限 Set admin_user_roles:{user.id}
    const ADMIN_USER_TWO_STEP = 'admin_user_two_step' . self::DELIMIT; // 用户权限 String admin_user_two_step:{user.id}
    const ADMIN_MFA_SECRET = 'admin_mfa_secret' . self::DELIMIT; // 用户权限 String admin_mfa_secret:{user.id}


}