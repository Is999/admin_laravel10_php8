<?php

namespace App\Enum;

use ReflectionClass;

/**
 *  file.status 字段状态
 */
enum FileStatus: int
{
    // 注意title值的顺序要和case定义一致
    private const title = ['未使用', '使用中', '待删除', '已删除'];
    case UNUSED = 0; // 未使用
    case USING = 1; // 使用中
    case TOBEDELETED = 2; // 待删除
    case DELETED = 3; // 已删除

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