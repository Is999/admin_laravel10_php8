<?php

namespace App\Enum;

/**
 *  sql排序字段状态
 */
enum OrderBy: string
{
    case ASC = 'ascend'; // 升序
    case DESC = 'descend'; // 降序

    /**
     * label 获取label值
     * @return string
     */
    public function label(): string
    {
        // 由于值和名称一样也可以直接使用 name, 等同于 $this->name
        return self::getLabel($this->value);
    }

    /**
     * getLabel 获取label值
     * @param $value
     * @return string
     */
    public static function getLabel($value): string
    {
        return match ($value) {
            self::ASC->value => 'ASC',
            default => 'DESC',
        };
    }
}