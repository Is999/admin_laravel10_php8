<?php

namespace App\Enum;

/**
 *  日志通道
 */
enum LogChannel: string
{
    case DEFAULT = ''; // 默认通道
    case DEV = 'devLog';
    case SQL = 'sqlLog';
    case GATEWAY = 'gatewayLog';
    case SYSLOG = 'syslog';
    case ERROR = 'errorlog';

    public static function checkChannel($channel)
    {
        if ($channel instanceof self) {
            return true;
        } else {
            return self::tryFrom($channel) !== null;
        }
    }
}
