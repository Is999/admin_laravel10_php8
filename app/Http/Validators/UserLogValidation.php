<?php

namespace App\Http\Validators;

use App\Enum\Code;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class UserLogValidation extends BaseValidation
{
    /**
     * 操作日志验证规则
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
                'user_name' => 'string|max:20', // 用户账户
                'action' => [ // 动作名称
                    new Enum(UserAction::class),
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
