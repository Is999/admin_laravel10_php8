<?php

namespace App\Enum;

use ReflectionClass;

/**
 *  校验MFA设备应用场景 CHECK_MFA_SCENARIOS
 */
enum CheckMfaScenarios: int
{
    // 注意title值的顺序要和case定义一致
    private const title = ['登录', '修改密码', '修改MFA状态', '修改MFA秘钥', '修改用户状态', '添加用户', '修改用户'];
    case LOGIN = 0; // 登录
    case CHANGE_PASSWORD = 1; // 修改密码
    case MFA_STATUS = 2; // 修改MFA状态（关闭）
    case MFA_SECURE_KEY = 3; // 修改MFA秘钥
    case USER_STATUS = 4; // 修改用户状态
    case ADD_USER = 5; // 添加用户
    case EDIT_USER = 6; // 修改用户

    /**
     *  通过反射获取所有定义的const和case参数
     * @return array
     */
    public static function toArray(): array
    {
        $reflection = new ReflectionClass(self::class);
        return $reflection->getConstants();
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

    /**
     * 映射title
     * @return array
     */
    public static function titleMap(): array
    {
        return array_combine(self::values(), self::title);
    }

    /**
     * 获取所有cases 值
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
