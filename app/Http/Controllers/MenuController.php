<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\MenuShortcut;
use App\Enum\MenuStatus;
use App\Enum\MenuType;
use App\Enum\OrderBy;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\MenuService;
use App\Services\ResponseService as Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Throwable;

class MenuController extends Controller
{
    /**
     * 菜单管理(页面列表/搜索)
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
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
                    'field' => 'string', // 排序字段
                    'order' => [ // 排序方式
                        new Enum(OrderBy::class),
                    ],
                    'cache' => 'boolean' // true 查缓存, 1 查表
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            // 查询数据
            $result = (new MenuService)->menuList($validator->validated());
            return Response::success($result);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 新增
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        try {
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

            $input = $validator->validated();

            // 新增权限
            $result = (new MenuService)->menuAdd($input);
            if (!$result) {
                throw new CustomizeException(Code::F2000);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::ADD_MENU, 'title=' . $input['title'], $input);

            return Response::success([], Code::S1000);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 编辑
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
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

            $input = $validator->validated();

            // 编辑权限
            $result = (new MenuService)->menuEdit($id, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2001);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_MENU, 'title=' . $input['title'], $input);
            return Response::success([], Code::S1001);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 显示/隐藏
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function editStatus(Request $request, int $id): JsonResponse
    {
        try {
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

            $input = $validator->validated();

            // 编辑角色状态
            $result = (new MenuService)->menuEdit($id, $input);
            if (!$result) {
                throw new CustomizeException($request->input('status') ? Code::F2006 : Code::F2007);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_STATUS_MENU, 'menu.id=' . $id, $input);

            return Response::success([], $request->input('status') ? Code::S1006 : Code::S1007);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }


    /**
     * 导航栏
     * @param Request $request
     * @return JsonResponse
     */
    public function nav(Request $request): JsonResponse
    {
        try {
            // 查询数据
            $result = (new MenuService)->getMenuNav($request->offsetGet('user.id'));
            return Response::success($result);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 菜单新增|编辑上级菜单(下拉框)
     * @param Request $request
     * @return JsonResponse
     */
    public function treeList(Request $request): JsonResponse
    {
        try {
            // 查询数据
            $result = (new MenuService)->menuTreeList();
            return Response::success($result);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 菜单
     * @param Request $request
     * @return JsonResponse
     */
    public function permissionUuidTreeList(Request $request): JsonResponse
    {
        try {
            // 查询数据
            $result = (new MenuService)->menuPermissionUuid();
            return Response::success($result);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

}
