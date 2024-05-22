<?php

namespace App\Services;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

class Service
{
    /**
     * 获取一个Redis对象
     * @param string|null $name
     * @return Connection|\Redis
     */
    public static function redis(string $name = null): Connection|\Redis
    {
        return Redis::connection($name);
    }
}