<?php

namespace App\Http\Validators;

use App\Enum\OrderBy;
use Illuminate\Validation\Rules\Enum;

class BaseValidation
{
    /**
     * 排序参数验证规则
     * @return array
     */
    public function orderByRule():array
    {
        // 验证参数
        return [
            'field' => 'string', // 排序字段
            'order' => [ // 排序方式
                new Enum(OrderBy::class),
            ],
            'page' => 'integer|min:1', // 页码
            'pageSize' => 'integer|between:10,100', // 每页条数
        ];
    }

    /**
     * 分页验证规则
     * @return array
     */
    public function limitRule(): array
    {
        // 验证参数
        return [
            'field' => 'string', // 排序字段
            'order' => [ // 排序方式
                new Enum(OrderBy::class),
            ],
            'page' => 'integer|min:1', // 页码
            'pageSize' => 'integer|between:10,100', // 每页条数
        ];
    }
}
