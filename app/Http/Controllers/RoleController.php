<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\Delete;
use App\Enum\LogChannel;
use App\Enum\RoleStatus;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Http\Validators\RoleValidation;
use App\Logging\Logger;
use App\Services\ResponseService as Response;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Throwable;

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
            $result = (new RoleService)->roleTreeList();
            return Response::success($result);
        } catch (Throwable $e) {
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
            $input = (new RoleValidation())->index($request);

            // 查询数据
            $result = (new RoleService)->roleList($input);
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
     * 新增角色
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new RoleValidation())->add($request);

            // 新增角色
            $admin = $request->offsetGet('user.id');
            $result = (new RoleService)->roleAdd($admin, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2000);
            }
            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::ADD_ROLE, "title={$input['title']}", $input);
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
     * 编辑角色
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new RoleValidation())->edit($request);

            // 编辑角色信息
            $admin = $request->offsetGet('user.id');
            $result = (new RoleService)->roleEdit($admin, $id, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2001);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_ROLE, "title={$input['title']}", $input);
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
     * 删除角色
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function del(Request $request, int $id): JsonResponse
    {
        try {
            // 删除角色信息
            $admin = $request->offsetGet('user.id');
            $result = (new RoleService)->roleEdit($admin, $id, ['is_delete' => Delete::YES]);
            if (!$result) {
                throw new CustomizeException(Code::F2002);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::DEL_ROLE, 'role.id=' . $id);
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
     * 启用禁用(角色状态)
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function editStatus(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new RoleValidation())->editStatus($request);

            // 编辑角色状态
            $admin = $request->offsetGet('user.id');
            $result = (new RoleService)->roleEdit($admin, $id, $input);
            if (!$result) {
                throw new CustomizeException($request->input('status') ? Code::F2004 : Code::F2005);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_STATUS_ROLE, 'role.id=' . $id, $input);
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
     * 编辑角色权限
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function editPermission(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new RoleValidation())->editPermission($request);

            // 编辑角色权限
            $admin = $request->offsetGet('user.id');
            $result = (new RoleService)->roleEdit($admin, $id, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2003);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_PERMISSION_ROLE, 'role.id=' . $id, $input);
            return Response::success([], Code::S1003);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 角色权限
     * @param Request $request
     * @param int $id
     * @param string $isPid
     * @return JsonResponse
     */
    public function permission(Request $request, int $id, string $isPid): JsonResponse
    {
        try {
            $admin = $request->offsetGet('user.id');
            $result = (new RoleService)->rolePermission($admin, $id, $isPid == 'y');
            return Response::success($result);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail();
        }
    }
}
