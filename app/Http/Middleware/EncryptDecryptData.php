<?php

namespace App\Http\Middleware;

use App\Enum\Code;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Enum\SecretKeyStatus;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\ResponseService as Response;
use App\Services\SecretKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class EncryptDecryptData
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (!empty($request->header('X-Cipher')) && (!empty($request->input('ciphertext', '')) || !empty($request->getContent()))) {
                // 请求头中获取
                $appKey = $request->header('X-App-Key', '');

                // 请求主体中获取
                if (empty($appKey)) {
                    $appKey = $request->input('appKey', '');
                }

                if (empty($appKey)) {
                    throw new CustomizeException(Code::E100062, ['param' => 'appKey']);
                }
                $appKey = base64_decode($appKey);
                if (!$appKey) {
                    throw new CustomizeException(Code::E100063, ['param' => 'appKey']);
                }

                // 获取apiKey配置
                $aes = (new SecretKeyService)->aesKey($appKey);
                if (!$aes) {
                    throw new CustomizeException(Code::E100064);
                }

                // 验证apiKey状态
                if ($aes['status'] != SecretKeyStatus::ENABLED) {
                    throw new CustomizeException(Code::E100065);
                }

                // 获取配置key
                if (empty($aes['key'])) {
                    throw new CustomizeException(Code::F5003, ['flag' => '001']);
                }
                $key = Crypt::decrypt($aes['key']); // 对key解密
                if (!in_array(strlen($key), [16, 24, 32])) {
                    Logger::error(LogChannel::DEV, 'AES key配置错误', [
                        'key' => $key,
                        'config' => $aes
                    ]);
                    throw new CustomizeException(Code::F5003, ['flag' => '001-' . strlen($key)]);
                }

                // 获取配置iv
                if (empty($aes['iv'])) {
                    throw new CustomizeException(Code::F5003, ['flag' => '002']);
                }
                $iv = Crypt::decrypt($aes['iv']); // 对iv解密
                if (16 != strlen($iv)) {
                    Logger::error(LogChannel::DEV, 'AES iv配置错误', [
                        'iv' => $iv,
                        'config' => $aes
                    ]);
                    throw new CustomizeException(Code::F5003, ['flag' => '001-' . strlen($iv)]);
                }

                // 解密请求数据
                $originalData = $request->input('ciphertext', '');
                if(empty($originalData)){
                    $originalData = $request->getContent();
                }
                $decryptedData = aesDecrypt($originalData, $key, $iv);
                if (empty($decryptedData)) {
                    Logger::error(LogChannel::DEV, '解密失败', [
                        'key' => $key,
                        'iv' => $iv,
                        'config' => $aes,
                        'ciphertext' => $originalData,
                    ]);

                    throw new CustomizeException(Code::E100067);
                }

                // 将解密后的数据设置回请求对象
                $data = json_decode($decryptedData, true);
                foreach ($data as $key => $value) {
                    $request->merge([$key => $value]);
                }

                // 执行下一个中间件
                $response = $next($request);

                // 加密响应数据
                $content = $response->getContent();
                if ($content) {
                    $response->headers->set('X-Cipher', 'AES');
                    $encryptedData = aesEncrypt(json_encode($response->original), $key, $iv);
                    // 设置加密后的数据为响应内容
                    $response->setContent($encryptedData);
                }
                return $response;
            }
            return $next($request);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage(), HttpStatus::INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, 'Secret', [
                'token' => $request->bearerToken(),
            ], $e);
            return Response::fail(Code::SYSTEM_ERR, null, HttpStatus::INTERNAL_SERVER_ERROR);
        }
    }
}
