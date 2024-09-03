<?php

namespace App\Http\Controllers;

use App\Enum\AppEnv;
use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\MenuShortcut;
use App\Enum\MenuStatus;
use App\Enum\MenuType;
use App\Enum\OrderBy;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Http\Validators\MenuValidation;
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
            $input = (new MenuValidation())->index($request);

            // 查询数据
            $result = (new MenuService)->menuList($input);
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
     * 新增菜单
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
            $input = (new MenuValidation())->add($request);

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
     * 编辑菜单
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new MenuValidation())->edit($request);

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
     * 编辑菜单状态[显示/隐藏]
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function editStatus(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new MenuValidation())->editStatus($request);

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
