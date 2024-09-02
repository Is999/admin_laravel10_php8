<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\OrderBy;
use App\Enum\SecretKeyStatus;
use App\Exceptions\CustomizeException;
use App\Http\Validators\SecretKeyValidation;
use App\Logging\Logger;
use App\Services\ResponseService as Response;
use App\Services\SecretKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Throwable;

class SecretKeyController extends Controller
{
    /**
     * 秘钥管理列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new SecretKeyValidation())->index($request);

            // 查询数据
            $result = (new SecretKeyService())->list($input);
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
                    'uuid' => 'required|string|max:100', // 唯一标识(前端权限标识)
                    'title' => 'required|string|max:100', // 权限名称
                    'module' => 'nullable|string|max:250', // 后端权限匹配模型(路由名称 | 控制器/方法)
                    'type' => [ // 类型
                        'required',
                        new Enum(PermissionType::class),
                    ],
                    'status' => [ // 状态：1 启用, 0 禁用
                        'required',
                        new Enum(SecretKeyStatus::class),
                    ],
                    'pid' => 'required|integer|min:0', // 上级id
                    'describe' => 'required|string|max:255',
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            // 新增权限
            $result = (new AuthorizeService)->permissionAdd($input);
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
                    'title' => 'required|string|max:100', // 权限名称
                    'module' => 'nullable|string|max:250', // 后端权限匹配模型(路由名称 | 控制器/方法)
                    'type' => [ // 类型
                        'required',
                        new Enum(PermissionType::class),
                    ],
                    'status' => [ // 状态：1 启用, 0 禁用
                        'required',
                        new Enum(SecretKeyStatus::class),
                    ],
                    'describe' => 'required|string|max:255',
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            // 编辑权限
            $result = (new AuthorizeService)->permissionEdit($id, $input);
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
            $validator = Validator::make($request->input()
                , [
                    'status' => [ // 状态：1 启用, 0 禁用
                        'required',
                        new Enum(SecretKeyStatus::class),
                    ],
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            // 更新权限状态
            $result = (new SecretKeyService())->permissionEdit($id, $input);
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

}
