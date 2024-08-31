<?php
/**
 * 对响应和请求的`敏感数据`进行签名和验签
 *
 * 签名和验签支持 RSA、AES、MD5
 *
 * 签名及验证签名规则在 \App\Enum\SignRules 定义
 */
namespace App\Http\Middleware;

use App\Contracts\Signature;
use App\Enum\Code;
use App\Enum\ConfigUuid;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Enum\SignatureType;
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
use RedisException;
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
                $signatureType = strtoupper($request->header('X-Signature', SignatureType::RSA->value)); // 签名、验签方式

                $signParams = $this->must[$routeName];
                $requestSign = isset($signParams['request']);
                $responseSign = isset($signParams['response']);
                if (true !== ConfigService::getCache(ConfigUuid::ADMIN_SIGNATURE_DISABLE) && $requestSign) {
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

                // 设置响应头签名方式
                $response->header('X-Signature', $signatureType);

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
            } else {
                $response = $next($request);
            }
            return $response;
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
     * 获取签名、验签方式
     * @param string $appId
     * @param string $signatureType M: MD5签名、验签；A: AES签名、验签；R: RSA签名、验签
     * @param bool $isVerify true 验证签名， false 签名
     * @return Signature
     * @throws CustomizeException|RedisException
     */
    public function getSignature(string $appId, string $signatureType, bool $isVerify = false): Signature
    {
        /* @var $signature Signature */
        if ($signatureType == SignatureType::MD5->value) {
            $signature = new Md5SignatureService;
        } elseif ($signatureType == SignatureType::AES->value) {
            $aes = (new SecretKeyService)->getAesKeyByRequestAppId($appId);
            $signature = new AesService($aes['key'], $aes['iv']);
        } elseif ($signatureType == SignatureType::RSA->value) {
            $key = (new SecretKeyService)->getRsaKeyByRequestAppId($appId, $isVerify ? SecretKeyService::USER_PUBLIC_KEY : SecretKeyService::SERVER_PRIVATE_KEY); // 公钥验签，私钥签名
            $signature = new RsaService($key);
        } else {
            throw new CustomizeException(Code::F10005, ['type' => $signatureType]);
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
        // 按ASCII编码顺序排序
        sort($signParams);

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
