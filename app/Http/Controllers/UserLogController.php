<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Http\Validators\UserLogValidation;
use App\Logging\Logger;
use App\Services\ResponseService as Response;
use App\Services\UserLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UserLogController extends Controller
{
    /**
     * 操作日志
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserLogValidation())->index($request);

            // 查询数据
            $result = (new UserLogService())->list($input);
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
     * 操作类型列表
     * @param Request $request
     * @return JsonResponse
     */
    public function actionList(Request $request): JsonResponse
    {
        try {
            return Response::success(UserAction::forSelect());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }
}
