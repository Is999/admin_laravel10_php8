<?php

namespace App\Http\Controllers;

use App\Enum\AppEnv;
use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Http\Validators\ConfigValidation;
use App\Logging\Logger;
use App\Services\ConfigService;
use App\Services\ResponseService as Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ConfigController extends Controller
{

    /**
     * 字典管理列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new ConfigValidation())->index($request);

            $adminId = $request->offsetGet('user.id');
            // 查询数据
            $result = (new ConfigService)->list($input, $adminId);
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
     * 添加字典
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
            $input = (new ConfigValidation())->add($request);

            $config = new ConfigService();

            // 校验值
            $input['example'] = $config->checkAndReformValue($input['type'], $input['example']);
            $input['value'] = $config->checkAndReformValue($input['type'], $input['value']);

            // 添加
            $result = $config->add($input);

            if (!$result) {
                throw new CustomizeException(Code::F2000);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::ADD_CONFIG, 'uuid=' . $input['uuid'], $input);
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
     * 修改字典
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new ConfigValidation())->edit($request);

            $config = new ConfigService();
            // 校验值
            $input['value'] = $config->checkAndReformValue($input['type'], $input['value']);

            $adminId = $request->offsetGet('user.id');
            // 编辑
            $result = $config->edit($id, $input, $adminId);

            if (!$result) {
                throw new CustomizeException(Code::F2001);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_CONFIG, 'uuid=' . $input['uuid'], $input);
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
     * 查看缓存中数据
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function getCache(Request $request, string $uuid): JsonResponse
    {
        try {
            // 获取缓存中的数据
            $result = ConfigService::getCache($uuid);
            return Response::success(['value' => $result]);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }


    /**
     * 查看缓存中数据
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function renew(Request $request, string $uuid): JsonResponse
    {
        try {
            // 获取缓存中的数据
            ConfigService::renew($uuid);

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::RENEW_CONFIG, 'uuid=' . $uuid);
            return Response::success([], Code::S1001);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }
}
