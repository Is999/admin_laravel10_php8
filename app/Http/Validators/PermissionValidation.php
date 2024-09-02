<?php

namespace App\Http\Validators;

use App\Enum\Code;
use App\Enum\OrderBy;
use App\Enum\PermissionStatus;
use App\Enum\PermissionType;
use App\Exceptions\CustomizeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class PermissionValidation extends BaseValidation
{
    /**
     * 权限管理列表验证规则
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
                'uuid' => 'string|max:100', // 唯一标识
                'title' => 'string|max:100', // 权限名称
                'module' => 'string|max:250', // 权限匹配模型(路由名称 | 控制器/方法)
                'type' => [ // 类型
                    'array',
                    Rule::in(PermissionType::values()),
                ],
                'status' => [ // 状态：1 启用, 0 禁用
                    new Enum(PermissionStatus::class),
                ],
                'pid' => 'integer|min:0', // 上级id
                'is_genealogy' => 'integer|min:0', // 0 直属, 1 族谱
                'cache' => 'boolean' // true 查缓存, 1 查表
            ]);

        // 排序规则
        $validator->addRules($this->orderByRule());

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 新增权限验证规则
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
                'uuid' => 'required|string|max:100', // 唯一标识(前端权限标识)
                'title' => 'required|string|max:100', // 权限名称
                'module' => 'nullable|string|max:250', // 后端权限匹配模型(路由名称 | 控制器/方法)
                'type' => [ // 类型
                    'required',
                    new Enum(PermissionType::class),
                ],
                'status' => [ // 状态：1 启用, 0 禁用
                    'required',
                    new Enum(PermissionStatus::class),
                ],
                'pid' => 'required|integer|min:0', // 上级id
                'describe' => 'required|string|max:255',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 编辑权限验证规则
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
                'title' => 'required|string|max:100', // 权限名称
                'module' => 'nullable|string|max:250', // 后端权限匹配模型(路由名称 | 控制器/方法)
                'type' => [ // 类型
                    'required',
                    new Enum(PermissionType::class),
                ],
                'status' => [ // 状态：1 启用, 0 禁用
                    'required',
                    new Enum(PermissionStatus::class),
                ],
                'describe' => 'required|string|max:255',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 编辑权限状态验证规则
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
                'status' => [ // 状态：1 启用, 0 禁用
                    'required',
                    new Enum(PermissionStatus::class),
                ],
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }
}
