<?php

use App\Enum\RedisKeys;
use App\Enum\RedisType;
use App\Models\Config;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SecretKey;

return [
    // index 命名为 key值第一个分割符之前的值,如: key=roles_permissions:{角色id}, index=roles_permissions
    // key 缓存的key 如: roles_permissions:
    // class  获取缓存数据类名
    // method 获取缓存数据方法名, 接收两个参数 key, index
    // type 缓存类型: String, Hash, List, Set, SortedSet
    // expires 过期时间 0 永久缓存, 否则过期时间=设置时间+随机时间(0-3600*24)

    // 角色状态
    'role_status' => [
        'title' => '角色状态',
        'key' => RedisKeys::ROLE_STATUS,
        'class' => Role::class,
        'method' => 'roleStatusHash',
        'type' => RedisType::Hash,
        'expires' => 0
    ],
    // 角色权限
    'role_permission' => [
        'title' => '角色权限',
        'combine' => ['id'],
        'key' => RedisKeys::ROLE_PERMISSION,
        'class' => Role::class,
        'method' => 'rolePermissionSet',
        'type' => RedisType::Set,
        'expires' => 0
    ],
    // 权限module
    'permission_module' => [
        'title' => '权限module',
        'key' => RedisKeys::PERMISSION_MODULE,
        'class' => Permission::class,
        'method' => 'permissionModuleHash',
        'type' => RedisType::Hash,
        'expires' => 0
    ],
    // 权限uuid
    'permission_uuid' => [
        'title' => '权限uuid',
        'key' => RedisKeys::PERMISSION_UUID,
        'class' => Permission::class,
        'method' => 'permissionUuidHash',
        'type' => RedisType::Hash,
        'expires' => 0
    ],
    // 前端菜单树数据
    'menu_tree' => [
        'title' => '菜单树',
        'key' => RedisKeys::MENU_TREE,
        'class' => Menu::class,
        'method' => 'menuTreeString',
        'type' => RedisType::String,
        'expires' => 0
    ],
    // 权限下拉框树数据
    'permission_tree' => [
        'title' => '权限树',
        'key' => RedisKeys::PERMISSION_TREE,
        'class' => Permission::class,
        'method' => 'permissionTreeString',
        'type' => RedisType::String,
        'expires' => 0
    ],
    // 角色下拉框树数据
    'role_tree' => [
        'title' => '角色树',
        'key' => RedisKeys::ROLE_TREE,
        'class' => Role::class,
        'method' => 'roleTreeString',
        'type' => RedisType::String,
        'expires' => 0
    ],
    // 字典配置
    'config_uuid' => [
        'title' => '字典配置',
        'combine' => ['uuid'],
        'key' => RedisKeys::CONFIG_UUID,
        'class' => Config::class,
        'method' => 'configUuidHash',
        'type' => RedisType::Hash,
        'expires' => 0
    ],
    // AES
    'secret_key_aes' => [
        'title' => 'AES',
        'combine' => ['uuid'],
        'key' => RedisKeys::SECRET_KEY_AES,
        'class' => SecretKey::class,
        'method' => 'secretKeyAESHash', // 阻止一次缓存全部数据，使用的时候再缓存
        'type' => RedisType::Hash,
        'expires' => 60 * 60 * 1, // 缓存1小时+随机时间(0-3600*24)
    ],
    // RSA
    'secret_key_rsa' => [
        'title' => 'RSA',
        'combine' => ['uuid'],
        'key' => RedisKeys::SECRET_KEY_RSA,
        'class' => SecretKey::class,
        'method' => 'secretKeyRSAHash', // 阻止一次缓存全部数据，使用的时候再缓存
        'type' => RedisType::Hash,
        'expires' => 60 * 60 * 1, // 缓存1小时+随机时间(0-3600*24)
    ],

];
