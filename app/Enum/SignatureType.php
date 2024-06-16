<?php

namespace App\Enum;

use ReflectionClass;

/**
 *  签名、验签方式
 */
enum SignatureType: string
{
    // 注意title值的顺序要和case定义一致
    private const title = ['MD5', 'AES', 'RSA'];
    case MD5 = 'M'; // MD5
    case AES = 'A'; // AES
    case RSA = 'R'; // RSA

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