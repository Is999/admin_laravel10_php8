<?php

namespace App\Enum;

/**
 *  http 状态码
 */
enum HttpStatus: int
{
    case SUCCESS = 200; // 成功
    case UNAUTHORIZED = 401; // 未授权
    case FORBIDDEN = 403; // 禁止访问
    case METHOD_NOT_ALLOWED = 405; // 不容许的请求方式
    case NOT_ACCEPTABLE = 406; // 无法处理请求内容
    case INTERNAL_SERVER_ERROR = 500; // 服务器错误
}
