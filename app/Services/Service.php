<?php

namespace App\Services;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RedisException;

class Service
{
    /**
     * 应用，多应用信息存储区分前缀
     */
    public static string $APP_NAME = '';

    public function __construct()
    {
        self::$APP_NAME = Str::slug(env('APP_NAME', 'laravel'), '_') . '_';
    }

    /**
     * 获取一个Redis对象
     * @param string|null $name
     * @return Connection|\Redis
     */
    public static function redis(string $name = null): Connection|\Redis
    {
        return Redis::connection($name);
    }

    /**
     * 将字符串值value关联到key并设置过期时间
     * @param string $key
     * @param mixed $value
     * @param int $timeout 时间秒
     * @return bool
     * @throws RedisException
     */
    public static function set(string $key, mixed $value, int $timeout = 0): bool
    {
        if ($timeout > 0) {
            return self::redis()->setex($key, $timeout, $value);
        }
        return self::redis()->set($key, $value);
    }

    /**
     * 获取缓存
     * @param string $key
     * @return mixed
     * @throws RedisException
     */
    public static function get(string $key): mixed
    {
        return self::redis()->get($key);
    }

    /**
     * 相当于array_walk_recursive, 可自定义递归字段
     * @param array $arr
     * @param callable $callback 返回true 终止
     * @param $child string 子集名称
     * @return array
     */
    public function arrayWalkRecursive(array &$arr, callable $callback, string $child = ''): array
    {
        array_walk($arr, function (&$item, $key) use (&$arr, $callback, $child) {
            $done = $callback($arr, $key, $item);
            if (!$done && $child && isset($arr[$key][$child]) && is_array($arr[$key][$child])) {
                $this->arrayWalkRecursive($arr[$key][$child], $callback, $child);
            }
        });

        // 重新排序
        $arr = array_values($arr);
        return $arr;
    }


    /**
     * 获取家谱中包含该pid的记录
     * @param Builder $query ;
     * @param int $pid 上级id
     * @param bool $isGenealogy true 查整个家谱包含 pid 的记录，false 查pid等于pid 的记录
     * @return mixed
     */
    public function getPids(Builder $query, int $pid, bool $isGenealogy = false): Builder
    {
        // 族谱
        if ($pid && $isGenealogy) {
            return $query->whereRaw('FIND_IN_SET(?, pids)', [$pid]);
        } elseif (!$pid && $isGenealogy) {
            return $query;
        } else {
            // 直属
            return $query->where('pid', $pid);
        }
    }
}
