<?php

namespace App\Http\Validators;

use App\Enum\Code;
use App\Enum\RedisType;
use App\Exceptions\CustomizeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class CacheValidation extends BaseValidation
{
    /**
     * 刷新缓存验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function renew(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'key' => 'required|string',
                'type' => [ // 类型: 1 菜单, 0 目录
                    'required',
                    new Enum(RedisType::class),
                ],
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 查看缓存key信息验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function keyInfo(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'key' => 'required|string',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 搜索key验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function searchKey(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'key' => 'required|string',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }
}
