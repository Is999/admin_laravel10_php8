<?php

namespace App\Http\Validators;

use App\Enum\Code;
use App\Enum\ConfigType;
use App\Exceptions\CustomizeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class ConfigValidation extends BaseValidation
{
    /**
     * 字典管理列表验证规则
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
                'uuid' => 'string', // 唯一标识
                'title' => 'string', // 名称
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

    /**
     * 添加字典验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function add(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'uuid' => 'required|string|max:255',
                'title' => 'required|string|max:255',
                'value' => 'required',
                'example' => 'required',
                'type' => [ // 类型
                    'required',
                    new Enum(ConfigType::class),
                ],
                'remark' => 'required|string|max:255',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 修改字典验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function edit(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'uuid' => 'required|string|max:255',
                'title' => 'required|string|max:255',
                'value' => 'required',
                'example' => 'required',
                'type' => [ // 类型
                    'required',
                    new Enum(ConfigType::class),
                ],
                'remark' => 'required|string|max:255',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }
}
