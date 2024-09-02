<?php

namespace App\Http\Validators;

use App\Enum\Code;
use App\Enum\RoleStatus;
use App\Exceptions\CustomizeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class RoleValidation extends BaseValidation
{
    /**
     * 角色管理列表验证规则
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
                'title' => 'string|max:100', // 角色名称
                'status' => [ // 角色状态
                    new Enum(RoleStatus::class),
                ],
                'pid' => 'integer|min:0', // 上级id
                'is_genealogy' => 'integer|min:0', // 0 直属, 1 族谱
                'cache' => 'boolean' // true 查缓存, 1 查表
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 新增角色验证规则
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
                'title' => 'required|string|max:100',
                'pid' => 'required|integer|min:1',
                'describe' => 'required|string|max:255',
                'permissions' => [
                    'required',
                    'array',
                ],
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 编辑角色验证规则
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
                'title' => 'required|string|max:100',
                'describe' => 'required|string|max:255',
                'status' => [
                    'required',
                    new Enum(RoleStatus::class),
                ],
                'permissions' => [
                    'required',
                    'array',
                ],
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 启用禁用(角色状态)验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function editStatus(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'title' => 'required|string|max:100',
                'describe' => 'required|string|max:255',
                'status' => [
                    'required',
                    new Enum(RoleStatus::class),
                ],
                'permissions' => [
                    'required',
                    'array',
                ],
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }


    /**
     * 编辑角色权限验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function editPermission(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'permissions' => [
                    'required',
                    'array',
                ],
                'is_extends' => [
                    'required',
                    'integer',
                ]
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }
}
