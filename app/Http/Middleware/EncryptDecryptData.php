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

use App\Enum\Code;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\ResponseService as Response;
use App\Services\SecretKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class EncryptDecryptData
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $aes = [];
            $cipher = $request->header('X-Cipher');
            if (!empty($cipher)) {
                $aes = (new SecretKeyService)->getAES($request);
                if (strtolower($cipher) === 'cipher') {
                    // 获取解密请求数据, 优先获取ciphertext参数
                    $originalData = $request->input('ciphertext');
                    if ($originalData === null) {
                        $originalData = $request->getContent();
                    }

                    if ($originalData !== null) {
                        // 解密请求数据
                        $decryptedData = aesDecrypt($originalData, $aes['key'], $aes['iv']);
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
                            if ($originalData !== null) {
                                // 解密请求的数据
                                $decryptedData = aesDecrypt($originalData, $aes['key'], $aes['iv']);
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

            // 响应数据加密：只对data下第一层数据加密
            $cipher = $response->headers->get("X-Cipher");
            $originalData = $response->getContent();
            if (!empty($cipher) && !empty($originalData)) {
                $aes = $aes ?: (new SecretKeyService)->getAES($request);

                if (strtolower($cipher) === 'cipher') {
                    $encryptedData = aesEncrypt($originalData, $aes['key'], $aes['iv']);
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

                                    Arr::set($content['data'], $param, aesEncrypt($originalData, $aes['key'], $aes['iv']));
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

}
