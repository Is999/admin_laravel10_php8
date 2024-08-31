<?php

namespace App\Enum;

/**
 *  日志通道
 */
enum LogChannel: string
{
    case DEFAULT = ''; // 默认通道
    case DEV = 'dev';
    case SQL = 'sql';
    case GATEWAY = 'gateway';
    case SYSLOG = 'syslog';
    case ERROR = 'errorlog';
    case CACHE = 'cache';

    public static function checkChannel($channel): bool
    {
        if ($channel instanceof self) {
            return true;
        } else {
            return self::tryFrom($channel) !== null;
        }
    }
}
