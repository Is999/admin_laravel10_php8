<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\OrderBy;
use App\Enum\SecretKeyStatus;
use App\Exceptions\CustomizeException;
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
            $validator = Validator::make($request->input()
                , [
                    'title' => 'string|max:100', // 标题
                    'uuid' => 'string|max:64', // 标题
                    'status' => [ // 状态
                        new Enum(SecretKeyStatus::class),
                    ],
                    'field' => 'string', // 排序字段
                    'order' => [ // 排序方式
                        new Enum(OrderBy::class),
                    ],
                    'page' => 'integer|min:1', // 页码
                    'pageSize' => 'integer|between:10,100', // 每页条数
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            // 查询数据
            $result = (new SecretKeyService())->list($request, $input);
            return Response::success($result);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

}