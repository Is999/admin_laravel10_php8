<?php

namespace App\Enum;

/**
 * 签名及验证签名规则
 * 配置路由以及请求和响应签名验签的参数，不配置则无须验证签名
 * request 请求签名参数
 * response 响应签名参数
 */
enum SignRules
{
    /**
     * 签名规则
     * key 路由
     * value 签名参数
     */
    const array signRules = [
        'user.login' => self::userLogin,
        'ignore.user.updateMfaSecureKey' => self::userUpdateMfaSecureKey,
        'ignore.user.updatePassword' => self::userUpdatePassword,
        'ignore.user.updateMine' => self::userEdit,
        'user.edit' => self::userEdit,
        'user.add' => self::userAdd,
    ];

    /**
     * 登录签名参数
     * request 请求签名参数
     * response 响应签名参数
     */
    const array userLogin = [
        'request' => ['name', 'password', 'secureCode'],
        'response' => ['token'],
    ];

    /**
     * 修改秘钥签名参数
     * request 请求签名参数
     * response 响应签名参数
     */
    const array userUpdateMfaSecureKey = [
        'request' => ['mfa_secure_key'],
    ];

    /**
     * 修改密码签名参数
     * request 请求签名参数
     * response 响应签名参数
     */
    const array userUpdatePassword = [
        'request' => ['passwordOld', 'passwordNew'],
    ];

    /**
     * 添加账号签名参数
     * request 请求签名参数
     * response 响应签名参数
     */
    const array userAdd = [
        'request' => ['name', 'email', 'phone', 'password', 'mfa_secure_key'],
    ];


    /**
     * 修改账号签名参数
     * request 请求签名参数
     * response 响应签名参数
     */
    const array userEdit = [
        'request' => ['name', 'email', 'phone', 'password', 'mfa_secure_key'],
    ];
}
