<?php

namespace App\Enum;

/**
 *  user.is_enabled 字段状态
 */
enum UserAction: string
{
    case LOGIN = '登录';
    case BUILD_SECRET_VERIFY_ACCOUNT = '绑定安全码账号验证';
    case LOGOUT = '登出';
    case UPDATE_PASSWORD = '更新密码';
    case UPDATE_SECURE_KEY = '更新安全秘钥';
    case BUILD_SECURE_KEY = '绑定安全码';
    case ADD_USER = '新增账号';
    case EDIT_USER = '编辑账号';
    case EDIT_STATUS_USER = '更新账号状态';
    case RENEW_CACHE = '刷新缓存';
    case RENEW_All_CACHE = '刷新全部缓存';
    case ADD_ROLES_USER = '分配用户角色';
    case EDIT_ROLES_USER = '编辑用户角色';
    case DEL_ROLES_USER = '解除用户角色';
    case ADD_ROLE = '新增角色';
    case EDIT_ROLE = '编辑角色';
    case EDIT_STATUS_ROLE = '更新角色状态';
    case EDIT_PERMISSION_ROLE = '更新角色权限';
    case DEL_ROLE = '删除角色';
    case ADD_CONFIG = '新增配置';
    case EDIT_CONFIG = '编辑配置';
    case RENEW_CONFIG = '刷新配置';
    case ADD_MENU = '新增菜单';
    case EDIT_MENU = '编辑菜单';
    case EDIT_STATUS_MENU = '更新菜单状态';
    case ADD_PERMISSION = '新增权限';
    case EDIT_PERMISSION = '编辑权限';
    case DEL_PERMISSION = '删除权限';


    /**
     *  通过反射获取所有定义的const和case参数
     * @return array
     */
    public static function toArray()
    {
        $reflection = new \ReflectionClass(self::class);
        return $reflection->getConstants();
    }


    /**
     * 获取所有cases 值
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }


    /**
     * 获取所有cases 值
     * @return array
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * 映射title
     * @return array
     */
    public static function titleMap(): array
    {
        return array_combine(self::values(), self::values());
    }

    /**
     * 下拉选择框
     * @return array
     */
    public static function forSelect(): array
    {
        $arr = [];
        foreach (self::titleMap() as $k => $v) {
            $arr[] = array('value' => $k, 'title' => $v);
        }
        return $arr;
    }
}