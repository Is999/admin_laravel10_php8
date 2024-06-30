<?php

use App\Http\Controllers\CacheController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SecretKeyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// 无须验证token和权限
Route::prefix('api')->group(function () {
    Route::post('user/login', [UserController::class, 'login'])->name('user.login'); // 登录
    //Route::post('user/buildSecretVerifyAccount', [UserController::class, 'buildSecretVerifyAccount'])->name('captcha.buildSecretVerifyAccount'); // 绑定安全码验证账号密码
});

// 须验证权限或登录Token
Route::prefix('api')->middleware(['adminAuth'])->group(function () {

    // except.不验证权限的接口(只验证token, 该路由无须加入权限表)
    Route::name('except.')->group(function () {
        // 角色
        Route::prefix('role')->name('role.')->group(function () {
            Route::get('treeList', [RoleController::class, 'treeList'])->name('treeList'); // 新增角色|编辑角色 上级角色(下拉框);
            Route::get('permission/{id}/{isPid}', [RoleController::class, 'permission'])->name('permission')->whereIn('isPid', ['y', 'n']); // 角色管理 角色权限
        });

        // 菜单
        Route::prefix('menu')->name('menu.')->group(function () {
            Route::get('nav', [MenuController::class, 'nav'])->name('nav'); // 菜单(左侧导航栏)
            Route::get('treeList', [MenuController::class, 'treeList'])->name('treeList'); // 新增菜单|编辑菜单 上级菜单(下拉框)
            Route::get('permissionUuidTreeList', [MenuController::class, 'permissionUuidTreeList'])->name('permissionUuidTreeList'); // 新增菜单|编辑菜单 权限标识(下拉框)
        });

        // 权限
        Route::prefix('permission')->name('permission.')->group(function () {
            Route::get('treeList', [PermissionController::class, 'treeList'])->name('treeList'); // 权限下拉列表
            Route::get('maxUuid', [PermissionController::class, 'maxUuid'])->name('maxUuid'); // 权限下拉列表
        });

        // 个人信息
        Route::prefix('user')->name('user.')->group(function () {
            Route::post('logout', [UserController::class, 'logout'])->name('logout'); // 登出
            Route::post('checkSecure', [UserController::class, 'checkSecure'])->name('checkSecure'); // 个人信息 校验安全码或密码（没有开启安全码校验则校验密码）
            Route::post('checkMfaSecure', [UserController::class, 'checkMfaSecure'])->name('checkMfaSecure'); // 个人信息 校验MFA动态密码并设置两步校验状态码
            Route::post('updatePassword', [UserController::class, 'updatePassword'])->name('updatePassword'); // 个人信息 安全设置 账号密码
            Route::post('updateMfaSecureKey', [UserController::class, 'updateMfaSecureKey'])->name('updateMfaSecureKey'); // 个人信息 安全设置 身份验证器(TOTP MFA 应用程序)
            Route::post('updateMfaStatus', [UserController::class, 'updateMfaStatus'])->name('updateMfaStatus'); // 个人信息 安全设置 修改MFA校验状态
            Route::get('mine', [UserController::class, 'mine'])->name('mine'); // 个人信息
            Route::post('updateMine', [UserController::class, 'updateMine'])->name('updateMine'); // 个人信息 基本设置 更新基本信息
            Route::post('updateAvatar', [UserController::class, 'updateAvatar'])->name('updateAvatar'); // 个人信息 基本设置 更换头像
            Route::get('permissions', [UserController::class, 'permissions'])->name('permissions'); // 当前登录用户 权限uuid控制 permission.uuid
            Route::get('roleTreeList', [UserController::class, 'roleTreeList'])->name('roleTreeList'); // 账号管理 查看账号校色 角色下拉框
            Route::get('roles/{id}', [UserController::class, 'roles'])->name('roles'); // 账号管理 获取账号{id}角色
            Route::get('buildMfaSecretKeyUrl/{id}', [UserController::class, 'buildMfaSecretKeyUrl'])->name('buildMfaSecretKeyUrl'); // 获取绑定安全秘钥的地址
        });

        // 操作日志
        Route::prefix('userLog')->name('userLog.')->group(function () {
            Route::get('actionList', [UserLogController::class, 'actionList'])->name('actionList'); // 操作类型下拉框
        });
    });

    //角色管理
    Route::prefix('role')->name('role.')->group(function () {
        Route::match(['GET', 'POST'], 'index', [RoleController::class, 'index'])->name('index'); // 列表,搜索
        Route::post('add', [RoleController::class, 'add'])->name('add'); // 添加
        Route::post('edit/{id}', [RoleController::class, 'edit'])->name('edit'); // 编辑
        Route::post('editStatus/{id}', [RoleController::class, 'editStatus'])->name('editStatus'); // 启用/禁用
        Route::post('editPermission/{id}', [RoleController::class, 'editPermission'])->name('editPermission'); // 编辑角色权限
        Route::post('del/{id}', [RoleController::class, 'del'])->name('del'); // 删除
    });

    // 权限管理
    Route::prefix('permission')->name('permission.')->group(function () {
        Route::match(['GET', 'POST'], 'index', [PermissionController::class, 'index'])->name('index'); // 列表,搜索
        Route::post('add', [PermissionController::class, 'add'])->name('add'); // 添加
        Route::post('edit/{id}', [PermissionController::class, 'edit'])->name('edit'); // 编辑
        Route::post('editStatus/{id}', [PermissionController::class, 'editStatus'])->name('editStatus'); // 启用/禁用
        Route::post('del/{id}', [PermissionController::class, 'del'])->name('del'); // 删除
    });

    // 菜单管理
    Route::prefix('menu')->name('menu.')->group(function () {
        Route::match(['GET', 'POST'], 'index', [MenuController::class, 'index'])->name('index'); // 列表,搜索
        Route::post('add', [MenuController::class, 'add'])->name('add'); // 添加
        Route::post('edit/{id}', [MenuController::class, 'edit'])->name('edit'); // 编辑
        Route::post('editStatus/{id}', [MenuController::class, 'editStatus'])->name('editStatus'); // 显示/隐藏
    });

    // 管理员
    Route::prefix('user')->name('user.')->group(function () {
        Route::match(['GET', 'POST'], 'index', [UserController::class, 'index'])->name('index'); // 账号管理 列表
        Route::get('roleList/{id}', [UserController::class, 'roleList'])->name('roleList'); // 用户角色
        Route::post('editRoles/{id}', [UserController::class, 'editRoles'])->name('editRoles'); // 给用户分配x角色
        Route::post('addRole/{id}', [UserController::class, 'addRole'])->name('addRole'); // 给用户分配角色
        Route::post('delRole/{id}', [UserController::class, 'delRole'])->name('delRole'); // 解除角色与用户的关系
        Route::post('add', [UserController::class, 'add'])->name('add'); // 添加账号
        Route::post('edit/{id}', [UserController::class, 'edit'])->name('edit'); // 编辑账号
        Route::post('editStatus/{id}', [UserController::class, 'editStatus'])->name('editStatus');  // 编辑账号状态 启用/禁用
        Route::post('editMfaStatus/{id}', [UserController::class, 'editMfaStatus'])->name('editMfaStatus');  // 编辑MFA校验状态
    });

    // 参数配置
    Route::prefix('config')->name('config.')->group(function () {
        Route::match(['GET', 'POST'], 'index', [ConfigController::class, 'index'])->name('index'); // 参数配置列表
        Route::post('add', [ConfigController::class, 'add'])->name('add'); // 添加配置
        Route::post('edit/{id}', [ConfigController::class, 'edit'])->name('edit'); // 编辑配置
        Route::get('getCache/{uuid}', [ConfigController::class, 'getCache'])->name('getCache'); // 查看配置缓存
        Route::post('renew/{uuid}', [ConfigController::class, 'renew'])->name('renew'); // 刷新配置缓存
    });

    // 缓存管理
    Route::prefix('cache')->name('cache.')->group(function () {
        Route::match(['GET', 'POST'], 'index', [CacheController::class, 'index'])->name('index'); // 缓存列表
        Route::get('serverInfo', [CacheController::class, 'serverInfo'])->name('serverInfo'); // 服务器信息
        Route::get('keyInfo', [CacheController::class, 'keyInfo'])->name('keyInfo'); // 查看缓存key信息
        Route::post('searchKey', [CacheController::class, 'searchKey'])->name('searchKey'); // 搜索key
        Route::post('searchKeyInfo', [CacheController::class, 'keyInfo'])->name('searchKeyInfo'); // 查看缓存key信息
        Route::post('renew', [CacheController::class, 'renew'])->name('renew'); // 刷新
        Route::post('renewAll', [CacheController::class, 'renewAll'])->name('renewAll'); // 刷新全部
    });

    // 操作日志
    Route::prefix('userLog')->name('userLog.')->group(function () {
        Route::match(['GET', 'POST'], 'index', [UserLogController::class, 'index'])->name('index'); // 缓存列表
    });

    // 秘钥管理
    Route::prefix('secretKey')->name('secretKey.')->group(function () {
        Route::match(['GET', 'POST'], 'index', [SecretKeyController::class, 'index'])->name('index'); // 秘钥管理列表
    });

});

//该路由放在文件最后
Route::fallback(function () {
    abort(404, '404 Not Found');
});
