<?php

namespace App\Http\Middleware;

use App\Enum\Code;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Enum\SignRules;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\ConfigService;
use App\Services\ResponseService as Response;
use App\Services\SecretKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class SignData
{
    /**
     * 必须签名的路由
     * @var array<string>
     */
    protected array $must = SignRules::signRules;

    public function handle(Request $request, Closure $next)
    {
        try {
            if (!ConfigService::getCache("SIGNATURE_DISABLE")) {
                // 获取路由
                $routeName = $request->route()->getName();
                if (isset($routeName, $this->must)) {
                    $requestId = $request->header('X-Request-Id', '');
                    $appId = $request->header('X-App-Id', '');

                    $signParams = $this->must[$routeName];
                    $requestSign = isset($signParams['request']);
                    $responseSign = isset($signParams['response']);

                    $rsa = ($requestSign || $responseSign) ? (new SecretKeyService)->getRsaKeyByRequestAppId($request, $requestSign, $responseSign) : [];

                    if ($requestSign) {
                        $input = $request->input();

                        // 验证签名是否存在
                        $sign = Arr::get($input, 'sign', '');
                        if (empty($sign)) {
                            if ($sign == '') {
                                throw new CustomizeException(Code::E100062, ['param' => 'sign']);
                            }
                        }

                        // 获取签名数据
                        $signStr = $this->getSignStr($input, $signParams['request'], $requestId, $appId);

                        // 验证签名
                        $checkResult = openssl_verify($signStr, base64_decode($sign), $rsa['user_public_key'], OPENSSL_ALGO_SHA256);
                        if ($checkResult !== 1) {
                            Logger::error(LogChannel::DEV, '签名错误', [
                                'signStr' => $signStr,
                                'sign' => $sign,
                                'checkResult' => $checkResult
                            ]);
                            throw new CustomizeException(Code::E100066);
                        }


                    }

                    $response = $next($request);

                    if ($responseSign) {
                        // 对返回数据进行签名
                        $content = $response->getContent();
                        if ($content) {
                            $content = json_decode($content, true);
                            $signStr = $this->getSignStr($content['data'], $signParams['response'], $requestId, $appId);
                            $signature = false;
                            openssl_sign($signStr, $signature, $rsa['server_private_key'], OPENSSL_ALGO_SHA256);
                            if (!$signature) {
                                throw new CustomizeException(Code::F5005);
                            }
                            $content['data']['sign'] = base64_encode($signature);
                            $response->setContent(json_encode($content));
                        }
                    }
                }
                return $response;
            }

            return $next($request);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, 'Sign', [
                'token' => $request->bearerToken(),
            ], $e);
            return Response::fail(Code::SYSTEM_ERR, null, HttpStatus::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 生成待签名的字符串
     * @param array $data 参与签名的参数数组
     * @return string 待签名的字符串
     * @throws CustomizeException
     */
    public function getSignStr(array $data, array $signParams, string $requestId, string $appId): string
    {
        // 参数key按ASCII编码顺序排序
        ksort($signParams);

        $str = '';
        foreach ($signParams as $k) {
            // 检查参数
            if (!array_key_exists($k, $data)) {
                throw new CustomizeException(Code::E100062, ['param' => $k]);
            }
            // 空字符串不参与签名
            if ($data[$k] === '' || $data[$k] === null) {
                continue;
            }
            $str .= $k . '=' . $data[$k] . '&';
        }

        return $str . "requestId=$requestId&key=$appId";
    }
}
