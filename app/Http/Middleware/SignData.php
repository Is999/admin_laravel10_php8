<?php

namespace App\Http\Middleware;

use App\Enum\Code;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Enum\SecretKeyStatus;
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
     * 不签名的路由
     *
     * @var array<string>
     */
    protected $except = [
        //
    ];

    public function handle(Request $request, Closure $next)
    {
        try {
            if (!ConfigService::getCache("SIGNATURE_DISABLE")) {
                // 获取路由
                $routeName = $request->route()->getName();
                if (!in_array($routeName, $this->except)) {
                    // 请求头中获取
                    $appKey = $request->header('X-App-Key', '');

                    // 请求主体中获取
                    if (empty($appKey)) {
                        $appKey = $request->input('appKey', '');
                    }

                    if (empty($appKey)) {
                        throw new CustomizeException(Code::E100062, ['param' => 'appKey']);
                    }
                    $appKey = base64_decode($appKey, true);
                    if (!$appKey) {
                        throw new CustomizeException(Code::E100063, ['param' => 'appKey']);
                    }

                    // 获取apiKey配置
                    $rsa = (new SecretKeyService)->rsaKey($appKey);
                    if (!$rsa) {
                        throw new CustomizeException(Code::E100064);
                    }

                    // 验证apiKey状态
                    if ($rsa['status'] != SecretKeyStatus::ENABLED) {
                        throw new CustomizeException(Code::E100065);
                    }

                    $input = $request->input();

                    // 验证签名是否存在
                    $sign = Arr::get($input, 'sign', '');
                    if (empty($sign)) {
                        if ($sign == '') {
                            throw new CustomizeException(Code::E100062, ['param' => 'sign']);
                        }
                    }

                    $filePath = storage_path($rsa['user_public_key']);
                    // 私钥签名，公钥验签，验证用户公钥文件是否存在
                    if (!$rsa['user_public_key'] || !file_exists($filePath)) {
                        Logger::error(LogChannel::DEV, '用户公钥文件不存在：' . $filePath);
                        throw new CustomizeException(Code::F5000, ['flag' => '003']);
                    }

                    // 获取文件中的公钥
                    $original = file_get_contents($filePath);
                    if ($original === false) {
                        Logger::error(LogChannel::DEV, '获取用户公钥文件内容失败：' . $filePath);
                        throw new CustomizeException(Code::F5001, ['flag' => '003']);
                    }

                    // 验证公钥是否是可用的
                    $publicKey = openssl_pkey_get_public($original);
                    if ($publicKey === false) {
                        Logger::error(LogChannel::DEV, '用户公钥无效：' . $filePath, [
                            'original' => $original
                        ]);
                        throw new CustomizeException(Code::F5002, ['flag' => '003']);
                    }

                    // 获取签名数据
                    $data = getSignStr($input) . '&appKey=' . $appKey;

                    // 验证签名
                    $checkResult = openssl_verify($data, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA512);
                    if ($checkResult !== 1) {
                        Logger::error(LogChannel::DEV, '签名错误', [
                            'data' => $data,
                            'sign' => $sign,
                            'filePath' => $filePath,
                            'checkResult' => $checkResult
                        ]);
                        throw new CustomizeException(Code::E100066);
                    }

                    $response = $next($request);

                    $filePath = storage_path($rsa['server_private_key']);
                    // 私钥签名，公钥验签，验证用户公钥文件是否存在
                    if (!$rsa['server_private_key'] || !file_exists($filePath)) {
                        Logger::error(LogChannel::DEV, '服务器私钥文件文件不存在：' . $filePath);
                        throw new CustomizeException(Code::F5000, ['flag' => '001']);
                    }

                    // 获取文件中的公钥
                    $original = file_get_contents($filePath);
                    if ($original === false) {
                        Logger::error(LogChannel::DEV, '获取服务器私钥文件内容失败：' . $filePath);
                        throw new CustomizeException(Code::F5001, ['flag' => '001']);
                    }

                    // 验证私钥是否是可用的
                    $privateKey = openssl_pkey_get_private($original);
                    if ($publicKey === false) {
                        Logger::error(LogChannel::DEV, '服务器私钥无效：' . $filePath, [
                            'original' => $original
                        ]);
                        throw new CustomizeException(Code::F5002, ['flag' => '001']);
                    }

                    // 对返回数据进行签名
                    $content = $response->getContent();
                    if ($content) {
                        $content = json_decode($content, true);
                        $data = getSignStr($content['data']) . '&appKey=' . $appKey;
                        $signature = '';
                        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
                        $content['data']['sign'] = base64_encode($signature);
                        $response->setContent(json_encode($content));
                    }

                    return $response;
                }
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
