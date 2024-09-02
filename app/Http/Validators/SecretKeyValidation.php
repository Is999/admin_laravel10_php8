<?php

namespace App\Http\Validators;

use App\Enum\Code;
use App\Enum\SecretKeyStatus;
use App\Exceptions\CustomizeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class SecretKeyValidation extends BaseValidation
{
    /**
     * 秘钥管理列表验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function index(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'title' => 'string|max:100', // 标题
                'uuid' => 'string|max:64', // 标题
                'status' => [ // 状态
                    new Enum(SecretKeyStatus::class),
                ],
            ]);

        // 排序规则
        $validator->addRules($this->orderByRule());
        // 分页参数
        $validator->addRules($this->limitRule());

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }
}
