<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Redis\Connections\Connection;

class Service
{
    /**
     * 获取一个Redis对象
     * @param string|null $name
     * @return Connection
     */
    public static function redis(string $name = null): Connection
    {
        return Redis::connection($name);
    }
}