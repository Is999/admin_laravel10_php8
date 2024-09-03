<?php

namespace App\Http\Controllers;

use App\Enum\AppEnv;
use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\OrderBy;
use App\Enum\PermissionStatus;
use App\Enum\PermissionType;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Http\Validators\PermissionValidation;
use App\Logging\Logger;
use App\Services\PermissionService;
use App\Services\ResponseService as Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Throwable;

class PermissionController extends Controller
{
    /**
     * 权限管理(页面列表/搜索)
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new PermissionValidation())->index($request);

            // 查询数据
            $result = (new PermissionService)->permissionList($input);
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
     * 新增权限
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        try {
            // 其它环境通过sql 添加
            if (strtolower(env('APP_ENV')) != AppEnv::DEV->value) {
                throw new CustomizeException(Code::INVALID_AUTHORIZATION);
            }

            // 验证参数
            $input = (new PermissionValidation())->add($request);

            // 新增权限
            $result = (new PermissionService)->permissionAdd($input);
            if (!$result) {
                throw new CustomizeException(Code::F2000);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::ADD_PERMISSION, 'title=' . $input['title'], $input);
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
     * 编辑权限
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new PermissionValidation())->edit($request);

            // 编辑权限
            $result = (new PermissionService)->permissionEdit($id, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2001);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_PERMISSION, 'title=' . $input['title'], $input);
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
     * 编辑状态 启用/禁用
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function editStatus(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new PermissionValidation())->editStatus($request);

            // 更新权限状态
            $result = (new PermissionService)->permissionEdit($id, $input);
            if (!$result) {
                throw new CustomizeException($request->input('status') ? Code::F2004 : Code::F2005);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_STATUS_PERMISSION, 'permissions.id=' . $id, $input);
            return Response::success([], $request->input('status') ? Code::S1004 : Code::S1005);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 删除
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function del(Request $request, int $id): JsonResponse
    {
        try {
            // 删除权限信息
            $result = (new PermissionService)->permissionDel($id);
            if (!$result) {
                throw new CustomizeException(Code::F2002);
            }
            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::DEL_PERMISSION, 'permissions.id=' . $id);
            return Response::success([], Code::S1002);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 上级权限
     * @param Request $request
     * @return JsonResponse
     */
    public function treeList(Request $request): JsonResponse
    {
        try {
            // 查询数据
            $result = (new PermissionService)->permissionTreeList();
            return Response::success($result);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 获取最大的uuid
     * @param Request $request
     * @return JsonResponse
     */
    public function maxUuid(Request $request): JsonResponse
    {
        try {
            // 查询数据
            return Response::success(['uuid' => (new PermissionService)->getMaxUuid()]);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }
}
