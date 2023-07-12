<?php

namespace App\Enum;

/**
 *  响应数据type字段状态
 */
enum ConfigType: int
{
    private const title = ['String', 'Integer', 'Float', 'Boolean', 'JsonArr', '分组标题'];
    case STRING_TYPE = 0; // string类型
    case INT_TYPE = 1; // int类型
    case FLOAT_TYPE = 2; // float类型
    case BOOL_TYPE = 3; // bool类型
    case JSON_ARR_TYPE = 4; // json格式会转换数组


    /**
     *  通过反射获取所有定义的const和case参数
     * @return array
     */
    public static function toArray()
    {
        $reflection = new \ReflectionClass(self::class);
        return $reflection->getConstants();
    }


    /**
     * 获取所有cases 值
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
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

}
