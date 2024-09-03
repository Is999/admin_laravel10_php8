<?php

namespace App\Http\Validators;

use App\Enum\CheckMfaScenarios;
use App\Enum\OrderBy;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class BaseValidation
{
    /**
     * 排序参数验证规则
     * @return array
     */
    public function orderByRule(): array
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
     * 分页参数验证规则
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

    /**
     * 两步验证参数验证规则
     * @param array $keys
     * @return array
     */
    public function twoStepRule(array $keys): array
    {
        return [
            'twoStepKey' => [
                'required',
                Rule::in(array_merge([CheckMfaScenarios::LOGIN->value], $keys)), // 允许使用登录身份验证信息
            ],
            'twoStepValue' => [
                'required',
            ],
        ];
    }
}
