<?php
/**
 * 对响应和请求的`敏感数据`进行加密解密
 *
 * 加密解密的参数放在header['X-Cipher']中：
 * 1. 整体加密解密：`X-Cipher`值等于cipher，加密解密ciphertext参数或body数据；
 * 2. 细分加密解密：`X-Cipher`值不等于cipher，原始类型是一个数组，进行了json编码和base64编码；
 *
 * 加密解密的参数只能是请求或响应的`首层数据`
 * Array或者Object类型的数据要标记`json:`标签
 */

namespace App\Http\Middleware;

use App\Contracts\Crypto;
use App\Enum\Code;
use App\Enum\CryptoType;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\AesService;
use App\Services\ResponseService as Response;
use App\Services\RsaService;
use App\Services\SecretKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class CryptoData
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $aes = [];
            $cipher = $request->header('X-Cipher');
            // 请求头中获取
            $appId = $request->header('X-App-Id', '');
            if (empty($appId)) {
                throw new CustomizeException(Code::E100062, ['param' => 'appId']);
            }
            $appId = base64_decode($appId);
            if (!$appId) {
                throw new CustomizeException(Code::E100063, ['param' => 'appId']);
            }
            $cryptoType = strtoupper($request->header('X-Crypto', CryptoType::AES->value)); // 加密、解密方式

            if (!empty($cipher)) {
                $crypto = $this->getCrypto($appId, $cryptoType, false);
                if (strtolower($cipher) === 'cipher') {
                    // 获取解密请求数据, 优先获取ciphertext参数
                    $originalData = $request->input('ciphertext');
                    if ($originalData === null) {
                        $originalData = $request->getContent();
                    }

                    if ($originalData !== null && $originalData !== '') {
                        // 解密请求数据
                        $decryptedData = $crypto->decrypt($originalData);
                        if ($decryptedData === false) {
                            Logger::error(LogChannel::DEV, '解密失败', [
                                'aes' => $aes,
                                'ciphertext' => $originalData,
                            ]);

                            throw new CustomizeException(Code::E100067, ['param' => '']);
                        }

                        // 将解密后的数据设置回请求对象
                        $data = json_decode($decryptedData, true);
                        foreach ($data as $k => $v) {
                            $request->merge([$k => $v]);
                        }
                    }
                } else {
                    $cipherParams = json_decode(base64_decode($cipher), true);
                    if (is_array($cipherParams)) {
                        // 获取解密请求数据
                        foreach ($cipherParams as $param) {
                            // 参数是否需要json处理
                            $isJson = str_starts_with($param, 'json:');
                            // 去掉json标签
                            if ($isJson) {
                                $param = ltrim($param, 'json:');
                            }

                            // 判断数据是否为空，空数据跳过解密步骤
                            $originalData = $request->input($param);
                            if ($originalData !== null && $originalData !== '') {
                                // 解密请求的数据
                                $decryptedData = $crypto->decrypt($originalData);
                                if ($decryptedData === false) {
                                    Logger::error(LogChannel::DEV, '解密失败', [
                                        'aes' => $aes,
                                        'param' => $param,
                                        'X-Cipher' => $cipherParams,
                                        'originalData' => $originalData,
                                    ]);
                                    throw new CustomizeException(Code::E100067, ['param' => $param]);
                                }

                                // 对json数据解码
                                if ($isJson && '' !== $decryptedData) {
                                    $decryptedData = json_decode($decryptedData);
                                    if ($decryptedData === null) {
                                        throw new CustomizeException(Code::F5004, ['flag' => 'json_decode:' . $param]);
                                    }
                                }

                                // 合并到请求参数中
                                $request->merge([$param => $decryptedData]);
                            }
                        }
                    }
                }
            }

            // 执行下一个中间件
            $response = $next($request);

            // 设置响应头加密方式
            $response->header('X-Crypto', $cryptoType);

            // 响应数据加密：只对data下第一层数据加密
            $cipher = $response->headers->get("X-Cipher");
            $originalData = $response->getContent();
            if (!empty($cipher) && !empty($originalData)) {
                $crypto = $this->getCrypto($appId, $cryptoType, true);

                if (strtolower($cipher) === 'cipher') {
                    $encryptedData = $crypto->encrypt($originalData);
                    if ($encryptedData === false) {
                        throw new CustomizeException(Code::F5009, ['param' => 'cipher']);
                    }
                    // 设置加密后的数据为响应内容
                    $response->setContent($encryptedData);
                } else {
                    $cipherParams = json_decode(base64_decode($response->headers->get("X-Cipher")));
                    if (is_array($cipherParams)) {
                        // 加密响应数据
                        $content = json_decode($originalData, true);
                        if ($content) {
                            // 处理加密数据
                            foreach ($cipherParams as $param) {
                                // 参数是否需要json处理
                                $isJson = str_starts_with($param, 'json:');
                                // 去掉json标签
                                if ($isJson) {
                                    $param = ltrim($param, 'json:');
                                }
                                // 判断数据是否存在该参数
                                if (array_key_exists($param, $content['data'])) {
                                    $originalData = Arr::get($content['data'], $param);
                                    // 加密非空数据
                                    if ($originalData !== '') {
                                        // 对json数据处理进行编码在加密
                                        if ($isJson) {
                                            // 判断数据类型，Array或者Object类型的数据才需要json编码
                                            if (!Arr::accessible($originalData)) {
                                                Logger::error(LogChannel::DEV, $param . '加密失败,无须json编码', [
                                                    'param' => $param,
                                                    'X-Cipher' => $cipherParams,
                                                    'originalData' => $originalData,
                                                ]);
                                                throw new CustomizeException(Code::F5004, ['flag' => 'json_encode:' . $param]);
                                            }
                                            $originalData = json_encode($originalData);
                                        }

                                        $encryptedData = $crypto->encrypt($originalData);
                                        if ($encryptedData === false) {
                                            throw new CustomizeException(Code::F5009, ['param' => $param]);
                                        }
                                        Arr::set($content['data'], $param, $encryptedData);
                                    }
                                }
                            }

                            // 设置加密后的数据为响应内容
                            $response->setContent(json_encode($content));
                        }
                    }
                }
            }

            return $response;
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, 'Secret', [
                'token' => $request->bearerToken(),
            ], $e);
            return Response::fail(Code::SYSTEM_ERR, null, HttpStatus::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 获取加密、解密方式
     * @param string $appId
     * @param string $cryptoType A: AES加密、解密；R: RSA加密、解密
     * @param bool $isEncrypt true 加密， false 解密
     * @return Crypto
     * @throws CustomizeException
     */
    public function getCrypto(string $appId, string $cryptoType, bool $isEncrypt = false): Crypto
    {
        /* @var $crypto Crypto */
        if ($cryptoType == CryptoType::RSA->value) {
            // 接口数据加密使用用户的公钥，解密使用服务器的私钥
            $key = (new SecretKeyService)->getRsaKeyByRequestAppId($appId, $isEncrypt ? SecretKeyService::USER_PUBLIC_KEY : SecretKeyService::SERVER_PRIVATE_KEY);
            $crypto = new RsaService($key);
        } elseif ($cryptoType == CryptoType::AES->value) {
            $aes = (new SecretKeyService)->getAesKeyByRequestAppId($appId);
            $crypto = new AesService($aes['key'], $aes['iv']);
        } else {
            throw new CustomizeException(Code::F10004, ['type' => $cryptoType]);
        }
        return $crypto;
    }

}
