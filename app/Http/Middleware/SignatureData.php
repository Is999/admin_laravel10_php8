<?php

namespace App\Http\Middleware;

use App\Contracts\Signature;
use App\Enum\Code;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Enum\SignRules;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\AesService;
use App\Services\ConfigService;
use App\Services\Md5SignatureService;
use App\Services\ResponseService as Response;
use App\Services\RsaService;
use App\Services\SecretKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class SignatureData
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

                if (isset($this->must[$routeName])) {
                    $requestId = $request->header('X-Request-Id', '');
                    $appId = $request->header('X-App-Id', '');
                    if (empty($appId)) {
                        throw new CustomizeException(Code::E100062, ['param' => 'appId']);
                    }
                    $appId = base64_decode($appId);
                    if (!$appId) {
                        throw new CustomizeException(Code::E100063, ['param' => 'appId']);
                    }
                    $signatureType = strtoupper($request->header('X-Signature', '')); // 签名方式

                    $signParams = $this->must[$routeName];
                    $requestSign = isset($signParams['request']);
                    $responseSign = isset($signParams['response']);

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
                        $signature = $this->getSignature($appId, $signatureType, true);
                        $checkResult = $signature->verify($signStr, $sign);
                        if (!$checkResult) {
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
                            if ($content['success']) { // 成功的数据签名
                                $signature = $this->getSignature($appId, $signatureType, false);
                                $signStr = $this->getSignStr($content['data'], $signParams['response'], $requestId, $appId);
                                $sign = $signature->sign($signStr);
                                if (!$sign) {
                                    throw new CustomizeException(Code::F5005);
                                }
                                $content['data']['sign'] = $sign;
                                $response->setContent(json_encode($content));
                            }
                        }
                    }
                    return $response;
                }
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
     * 获取签名方式
     * @param string $appId
     * @param string $signatureType
     * @param bool $isVerify true 验证签名， false 签名
     * @return Signature
     * @throws CustomizeException
     */
    public function getSignature(string $appId, string $signatureType, bool $isVerify = false): Signature
    {
        /* @var $signature Signature */
        if ($signatureType == 'M') {
            $signature = new Md5SignatureService;
        } elseif ($signatureType == 'A') {
            $aes = (new SecretKeyService)->getAesKeyByRequestAppId($appId);
            $signature = new AesService($aes['key'], $aes['iv']);
        } else {
            $key = (new SecretKeyService)->getRsaKeyByRequestAppId($appId, $isVerify ? SecretKeyService::USER_PUBLIC_KEY : SecretKeyService::SERVER_PRIVATE_KEY);
            $signature = new RsaService($key);
        }
        return $signature;
    }

    /**
     * 生成待签名的字符串
     * @param array $data 参与签名的参数数组
     * @return string 待签名的字符串
     */
    public function getSignStr(array $data, array $signParams, string $requestId, string $appId): string
    {
        // 参数key按ASCII编码顺序排序
        ksort($signParams);

        $str = '';
        foreach ($signParams as $k) {
            // 空字符串不参与签名
            if (!array_key_exists($k, $data) || $data[$k] === '' || $data[$k] === null) {
                continue;
            }
            $str .= $k . '=' . $data[$k] . '&';
        }

        return $str . 'key=' . md5($appId . '-' . $requestId);
    }
}
