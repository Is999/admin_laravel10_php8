<?php

namespace App\Enum;

use ReflectionClass;

/**
 *  config 数据类型
 */
enum AppEnv: string
{
    private const title = ['开发环境', '测试环境', '预发布环境', '生产环境'];
    case DEV = 'development'; // 开发环境
    case TEST = 'test'; // 测试环境
    case PRE = 'preproduction'; // 预发布环境
    case PRO = 'production'; // 生产环境

    /**
     *  通过反射获取所有定义的const和case参数
     * @return array
     */
    public static function toArray()
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
