<?php

namespace App\Enum;

/**
 * 签名及验证签名规则
 * 配置路由以及请求和响应签名验签的参数，不配置则无须验证签名
 */
enum SignRules
{
    /**
     * 签名规则
     * key 路由
     * value 签名参数
     */
    const signRules = [
        'user.login' => self::userLogin,
    ];

    /**
     * 签名参数
     * request 请求签名参数
     * response 响应签名参数
     */
    const userLogin = [
        'request' => ['name', 'password', 'secureCode'],
        'response' => ['token'],
    ];
}