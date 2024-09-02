<?php

namespace App\Http\Validators;

use App\Enum\Code;
use App\Enum\MenuShortcut;
use App\Enum\MenuStatus;
use App\Enum\MenuType;
use App\Exceptions\CustomizeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class MenuValidation extends BaseValidation
{
    /**
     * 菜单管理列表验证规则
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
                'permissions_uuid' => 'string|max:100', // 唯一标识
                'title' => 'string|max:100', // 菜单名称
                'pid' => 'integer|min:0', // 上级id
                'is_genealogy' => 'integer|min:0', // 0 直属, 1 族谱
                'status' => [ // 状态：1 显示, 0 隐藏
                    new Enum(MenuStatus::class),
                ],
                'is_shortcut' => [ // 是否快捷
                    new Enum(MenuShortcut::class),
                ],
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
     * 新增菜单验证规则
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
                'permissions_uuid' => 'required|string|max:100', // 权限标识
                'title' => 'required|string|max:100', // 菜单名称
                'title_lang' => 'required|string|max:200', // 菜单名称
                'icon' => 'required|string|max:255', // 图标
                'path' => 'string|max:255', // 路由地址
                'component' => 'string|max:255', // 组件路径
                'status' => [ // 状态: 1 显示, 0 隐藏
                    'required',
                    new Enum(MenuStatus::class),
                ],
                'is_shortcut' => [ // 是否快捷: 1 是, 0 否
                    'required',
                    new Enum(MenuShortcut::class),
                ],
                'type' => [ // 类型: 1 菜单, 0 目录
                    'required',
                    new Enum(MenuType::class),
                ],
                'pid' => 'required|integer|min:0', // 上级id
                'sort' => 'integer', // 排序
                'describe' => 'string|max:255', // 描述
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 编辑菜单验证规则
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
                'permissions_uuid' => 'required|string|max:100', // 权限标识
                'title' => 'required|string|max:100', // 菜单名称
                'title_lang' => 'required|string|max:200', // 菜单名称
                'icon' => 'required|string|max:255', // 图标
                'path' => 'string|max:255', // 路由地址
                'status' => [ // 状态: 1 显示, 0 隐藏
                    'required',
                    new Enum(MenuStatus::class),
                ],
                'is_shortcut' => [ // 是否快捷: 1 是, 0 否
                    'required',
                    new Enum(MenuShortcut::class),
                ],
                'type' => [ // 类型: 1 菜单, 0 目录
                    'required',
                    new Enum(MenuType::class),
                ],
                'pid' => 'required|integer|min:0', // 上级id
                'sort' => 'integer', // 排序
                'component' => 'string|max:255', // 组件路径
                'describe' => 'string|max:255',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 编辑菜单状态验证规则
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
                'status' => [
                    'required',
                    new Enum(MenuStatus::class),
                ],
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }
}
