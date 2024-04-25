<?php

use App\Http\Controllers\CacheController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
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
    Route::post('user/buildSecretVerifyAccount', [UserController::class, 'buildSecretVerifyAccount'])->name('captcha.buildSecretVerifyAccount'); // 绑定安全码验证账号密码
});

// 须验证权限或登录Token
Route::prefix('api')->middleware(['adminAuth'])->group(function () {

    // 不验证权限的接口(只验证token, 该路由无须加入权限表)
    Route::name('except.')->group(function () {
        // 角色
        Route::controller(RoleController::class)->prefix('role')->name('role.')->group(function () {
            Route::get('treeList', 'treeList')->name('treeList'); // 新增角色|编辑角色 上级角色(下拉框);
            Route::get('permission/{id}/{isPid}', 'permission')->name('permission')->whereIn('isPid',['y','n']); // 角色权限
        });

        // 菜单
        Route::controller(MenuController::class)->prefix('menu')->name('menu.')->group(function () {
            Route::get('nav', 'nav')->name('nav'); // 菜单(左侧导航栏)
            Route::get('treeList', 'treeList')->name('treeList'); // 新增菜单|编辑菜单 上级菜单(下拉框)
            Route::get('permissionUuidTreeList', 'permissionUuidTreeList')->name('permissionUuidTreeList'); // 新增菜单|编辑菜单 权限标识(下拉框)
        });

        // 权限
        Route::controller(PermissionController::class)->prefix('permission')->name('permission.')->group(function () {
            Route::get('treeList', 'treeList')->name('treeList'); // 权限下拉列表
            Route::get('maxUuid', 'maxUuid')->name('maxUuid'); // 权限下拉列表
        });

        // 用户
        Route::controller(UserController::class)->prefix('user')->name('user.')->group(function () {
            Route::post('logout', 'logout')->name('logout'); // 登出
            Route::post('checkSecure', 'checkSecure')->name('checkSecure'); // 校验安全码或密码（没有开启安全码校验则校验密码）
            Route::post('updatePassword', 'updatePassword')->name('updatePassword'); // 修改密码
            Route::post('updateSecureKey', 'updateSecureKey')->name('updateSecureKey'); // 修改安全秘钥
            Route::get('mine', 'mine')->name('mine'); // 个人信息
            Route::post('update/{id}', 'edit')->name('update'); // 编辑账号
            Route::get('permissions', 'permissions')->name('permissions'); // 用户权限uuid控制 permission.uuid
            Route::get('roleTreeList', 'roleTreeList')->name('roleTreeList'); // 角色下拉框
            Route::get('roles/{id}', 'roles')->name('roles'); // 角色下拉框
            Route::get('buildSecretKeyUrl/{id}', 'buildSecretKeyUrl')->name('buildSecretKeyUrl'); // 获取绑定安全秘钥的地址
        });

        // 操作日志
        Route::controller(UserLogController::class)->prefix('userlog')->name('userlog.')->group(function () {
            Route::get('actionList', 'actionList')->name('actionList'); // 操作类型下拉框
        });
    });

    //角色管理
    Route::controller(RoleController::class)->prefix('role')->name('role.')->group(function () {
        Route::match(['GET', 'POST'], 'index', 'index')->name('index'); // 列表,搜索
        Route::post('add', 'add')->name('add'); // 添加
        Route::post('edit/{id}', 'edit')->name('edit'); // 编辑
        Route::post('del/{id}', 'del')->name('del'); // 删除
        Route::post('editStatus/{id}', 'editStatus')->name('editStatus'); // 启用禁用
        Route::post('editPermission/{id}', 'editPermission')->name('editPermission'); // 编辑角色权限
    });

    // 权限管理
    Route::controller(PermissionController::class)->prefix('permission')->name('permission.')->group(function () {
        Route::match(['GET', 'POST'], 'index', 'index')->name('index'); // 列表,搜索
        Route::post('add', 'add')->name('add'); // 添加
        Route::post('edit/{id}', 'edit')->name('edit'); // 编辑
        Route::post('del/{id}', 'del')->name('del'); // 删除
    });

    // 菜单管理
    Route::controller(MenuController::class)->prefix('menu')->name('menu.')->group(function () {
        Route::match(['GET', 'POST'], 'index', 'index')->name('index'); // 列表,搜索
        Route::post('add', 'add')->name('add'); // 添加
        Route::post('edit/{id}', 'edit')->name('edit'); // 编辑
        Route::post('editStatus/{id}', 'editStatus')->name('editStatus'); // 显示/隐藏
    });

    // 管理员
    Route::controller(UserController::class)->prefix('user')->name('user.')->group(function () {
        Route::match(['GET', 'POST'], 'index', 'index')->name('index'); // 账号管理 列表
        Route::get('roleList/{id}', 'roleList')->name('roleList'); // 用户角色
        Route::post('editRoles/{id}', 'editRoles')->name('editRoles'); // 给用户分配x角色
        Route::post('addRole/{id}', 'addRole')->name('addRole'); // 给用户分配角色
        Route::post('delRole/{id}', 'delRole')->name('delRole'); // 解除角色与用户的关系
        Route::post('add', 'add')->name('add'); // 添加账号
        Route::post('edit/{id}', 'edit')->name('edit'); // 编辑账号
        Route::post('editStatus/{id}', 'editStatus')->name('editStatus');  // 编辑
    });

    // 参数配置
    Route::controller(ConfigController::class)->prefix('config')->name('config.')->group(function () {
        Route::match(['GET', 'POST'], 'index', 'index')->name('index'); // 参数配置列表
        Route::post('add', 'add')->name('add'); // 添加配置
        Route::post('edit/{id}', 'edit')->name('edit'); // 编辑配置
        Route::get('getCache/{uuid}', 'getCache')->name('getCache'); // 查看配置缓存
        Route::post('renew/{uuid}', 'renew')->name('renew'); // 刷新配置缓存
    });

    // 缓存管理
    Route::controller(CacheController::class)->prefix('cache')->name('cache.')->group(function () {
        Route::match(['GET', 'POST'], 'index', 'index')->name('index'); // 缓存列表
        Route::get('serverInfo', 'serverInfo')->name('serverInfo'); // 服务器信息
        Route::get('keyInfo', 'keyInfo')->name('keyInfo'); // 查看缓存key信息
        Route::post('searchKey', 'searchKey')->name('searchKey'); // 搜索key
        Route::post('searchKeyInfo', 'keyInfo')->name('searchKeyInfo'); // 查看缓存key信息
        Route::post('renew', 'renew')->name('renew'); // 刷新
        Route::post('renewAll', 'renewAll')->name('renewAll'); // 刷新全部
    });

    // 操作日志
    Route::controller(UserLogController::class)->prefix('userlog')->name('userlog.')->group(function () {
        Route::match(['GET', 'POST'], 'index', 'index')->name('index'); // 缓存列表
    });

});

//该路由放在文件最后
Route::fallback(function () {
    abort(404, '404 Not Found');
});
