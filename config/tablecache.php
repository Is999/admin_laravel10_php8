<?php

use App\Enum\RedisKeys;
use App\Enum\RedisType;
use App\Models\Config;
use App\Models\Menus;
use App\Models\Permissions;
use App\Models\Roles;

return [
    // index 命名为 key值第一个分割符之前的值,如: key=roles_permissions:{角色id}, index=roles_permissions
    // key 缓存的key 如: roles_permissions:
    // class  获取缓存数据类名
    // method 获取缓存数据方法名, 接收两个参数 key, index
    // type 缓存类型: String, Hash, List, Set, SortedSet
    // expires 过期时间 0 永久缓存, 否则过期时间=设置时间+随机时间(0-3600*24)

    // 角色状态
    'roles_status' => [
        'title' => '角色状态',
        'key' => RedisKeys::ROLES_STATUS,
        'class' => Roles::class,
        'method' => 'rolesStatusHashTable',
        'type' => RedisType::Hash,
        'expires' => 0
    ],
    // 角色权限
    'roles_permissions' => [
        'title' => '角色权限',
        'combine' => ['id'],
        'key' => RedisKeys::ROLES_PERMISSIONS,
        'class' => Roles::class,
        'method' => 'rolesPermissionsSetTable',
        'type' => RedisType::Set,
        'expires' => 0
    ],
    // 权限module
    'permissions_module' => [
        'title' => '权限module',
        'key' => RedisKeys::PERMISSIONS_MODULE,
        'class' => Permissions::class,
        'method' => 'permissionsModuleHashTable',
        'type' => RedisType::Hash,
        'expires' => 0
    ],
    // 权限uuid
    'permissions_uuid' => [
        'title' => '权限uuid',
        'key' => RedisKeys::PERMISSIONS_UUID,
        'class' => Permissions::class,
        'method' => 'permissionsUuidHashTable',
        'type' => RedisType::Hash,
        'expires' => 0
    ],
    // 前端菜单树数据
    'menus_tree' => [
        'title' => '菜单树',
        'key' => RedisKeys::MENUS_TREE,
        'class' => Menus::class,
        'method' => 'menusTreeStringTable',
        'type' => RedisType::String,
        'expires' => 0
    ],
    // 权限下拉框树数据
    'permissions_tree' => [
        'title' => '权限树',
        'key' => RedisKeys::PERMISSIONS_TREE,
        'class' => Permissions::class,
        'method' => 'permissionsTreeStringTable',
        'type' => RedisType::String,
        'expires' => 0
    ],
    // 角色下拉框树数据
    'role_tree' => [
        'title' => '角色树',
        'key' => RedisKeys::ROLE_TREE,
        'class' => Roles::class,
        'method' => 'roleTreeStringTable',
        'type' => RedisType::String,
        'expires' => 0
    ],
    // 参数配置
    'config_uuid' => [
        'title' => '参数配置',
        'combine' => ['uuid'],
        'key' => RedisKeys::CONFIG_UUID,
        'class' => Config::class,
        'method' => 'configsUuidHashTable',
        'type' => RedisType::Hash,
        'expires' => 0
    ],
];