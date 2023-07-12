<?php

namespace App\Services;

use App\Enum\Code;
use App\Enum\HttpStatus;
use App\Enum\ResponseStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class ResponseService extends Service
{

    /**
     * 请求响应失败 406 json
     * @param int $code 识别码
     * @param string|array|null $message
     * @param HttpStatus $status http 响应状态码
     * @return JsonResponse
     */
    public static function fail(int $code = Code::FAIL, mixed $message = null, HttpStatus $status = HttpStatus::NOT_ACCEPTABLE): JsonResponse
    {
        return self::json(ResponseStatus::FAIL, $code, $message, [], $status);
    }

    /**
     * 请求响应成功 200
     * @param array $data 数据
     * @param int $code code 码
     * @param string|array|null $message 消息
     * @return JsonResponse
     */
    public static function success(array $data = [], int $code = Code::SUCCESS, mixed $message = null): JsonResponse
    {
        return self::json(ResponseStatus::SUCCESS, $code, $message, $data);
    }

    /**
     * 响应json
     * @param bool $success true 成功，false 失败
     * @param int $code 识别码
     * @param string|array|null $message 消息
     * @param array $data
     * @param HttpStatus $httpStatus http 响应状态码
     * @return JsonResponse
     */
    public static function json(bool $success, int $code, mixed $message = null, array $data = [], HttpStatus $httpStatus = HttpStatus::SUCCESS): JsonResponse
    {
        $message = self::parseMessage($code, $message);

        return Response::json(compact('success', 'code', 'message', 'data'), $httpStatus->value);
    }

    /**
     * 多语言包 解析message
     * @param int $code
     * @param mixed $message
     * @return string
     */
    public static function parseMessage(int $code, mixed $message): string
    {
        if ($message === null || is_array($message)) {
            // 多语言包
            $context = __('message.' . $code);
            if ($context == 'message.' . $code) {
                return 'Message Exception: code[' . $code . '] not found !';
            }

            if (is_array($message)) {
                foreach ($message as $k => $v) {
                    $context = Str::replace('{' . $k . '}', $v, $context);
                }
            }

            return $context;
        }
        return $message;
    }
}
