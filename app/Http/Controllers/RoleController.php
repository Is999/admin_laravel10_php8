<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\Delete;
use App\Enum\LogChannel;
use App\Enum\RoleStatus;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\AuthorizeService;
use App\Services\ResponseService as Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class RoleController extends Controller
{
    /**
     * 角色列表(下拉框)
     * @param Request $request
     * @return JsonResponse
     */
    public function treeList(Request $request): JsonResponse
    {
        try {
            $result = AuthorizeService::roleTreeList($request);
            return Response::success($result);
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail();
        }
    }

    /**
     * 角色管理(页面列表/搜索)
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->all()
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

            // 查询数据
            $result = AuthorizeService::roleIndex($request, $validator->validated());
            return Response::success($result);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 新增角色
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->all()
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

            $input = $validator->validated();

            // 新增角色
            $result = AuthorizeService::roleAdd($request, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2000);
            }
            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::ADD_ROLE, "title={$input['title']}", $input);
            return Response::success([], Code::S1000);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 编辑角色
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->all()
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

            $input = $validator->validated();

            // 编辑角色信息
            $result = AuthorizeService::roleEdit($request, $id, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2001);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_ROLE, "title={$input['title']}", $input);
            return Response::success([], Code::S1001);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 删除角色
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function del(Request $request, int $id): JsonResponse
    {
        try {
            // 删除角色信息
            $result = AuthorizeService::roleEdit($request, $id, ['is_delete' => Delete::YES]);
            if (!$result) {
                throw new CustomizeException(Code::F2002);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::DEL_ROLE, 'role.id=' . $id);
            return Response::success([], Code::S1002);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 启用禁用(角色状态)
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function editStatus(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->all()
                , [
                    'status' => [
                        'required',
                        new Enum(RoleStatus::class),
                    ],
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            // 编辑角色状态
            $result = AuthorizeService::roleEdit($request, $id, $input);
            if (!$result) {
                throw new CustomizeException($request->input('status') ? Code::F2004 : Code::F2005);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_STATUS_ROLE, 'role.id=' . $id, $input);
            return Response::success([], $request->input('status') ? Code::S1004 : Code::S1005);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 编辑角色权限
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function editPermission(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->all()
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

            $input = $validator->validated();
            // 编辑角色权限
            $result = AuthorizeService::roleEdit($request, $id, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2003);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_PERMISSION_ROLE, 'role.id=' . $id, $input);
            return Response::success([], Code::S1003);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 角色权限
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function permission(Request $request, int $id): JsonResponse
    {
        try {
            $result = AuthorizeService::permission($request, $id);
            return Response::success($result);
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail();
        }
    }
}
