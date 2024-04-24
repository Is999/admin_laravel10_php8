<?php

namespace App\Services;

use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\RedisKeys;
use App\Enum\SecretKeyStatus;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SecretKeyService extends Service
{
    /**
     * @param Request $request
     * @return array
     * @throws CustomizeException
     */
    public function getAES(Request $request): array
    {
        $aes = $this->getSecretKey($request,'AES');
        // 获取配置key
        if (empty($aes['key'])) {
            throw new CustomizeException(Code::F5003, ['flag' => '001']);
        }
        $key = Crypt::decryptString($aes['key']);// 对key解密
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
        $iv = Crypt::decryptString($aes['iv']);// 对iv解密
        if (16 != strlen($iv)) {
            Logger::error(LogChannel::DEV, 'AES iv配置错误', [
                'iv' => $iv,
                'config' => $aes
            ]);
            throw new CustomizeException(Code::F5003, ['flag' => '001-' . strlen($iv)]);
        }
        return ['key' => $key, 'iv' => $iv];
    }

    /**
     * 获取配置
     * @param Request $request
     * @param string $type
     * @return array
     * @throws CustomizeException
     */
    public function getSecretKey(Request $request, string $type): array
    {
        // 请求头中获取
        $appId = $request->header('X-App-Id', '');// 请求主体中获取
        if (empty($appId)) {
            $appId = $request->input('appId', '');
        }
        if (empty($appId)) {
            throw new CustomizeException(Code::E100062, ['param' => 'appId']);
        }
        $appId = base64_decode($appId);
        if (!$appId) {
            throw new CustomizeException(Code::E100063, ['param' => 'appId']);
        }

        // 获取appId配置
        $keys = [];
        if ($type === 'AES') {
            $keys = $this->aesKey($appId);
        } elseif ($type === 'RSA') {
            $keys = $this->rsaKey($appId);
        }

        if (!$keys) {
            throw new CustomizeException(Code::E100064);
        }
        // 验证appId状态
        if ($keys['status'] != SecretKeyStatus::ENABLED->value) {
            throw new CustomizeException(Code::E100065);
        }
        return $keys;
    }

    public function aesKey(string $uuid, bool $renew = true): array
    {
        // 获取缓存
        $key = RedisKeys::SECRET_KEY_AES . $uuid;
//        if (!$renew) {
//            if (self::redis()->exists($key)) {
//                return self::redis()->hMGet($key, ['aes_key', 'aes_iv']);
//            }
//        }else{
//            self::redis()->del($key);
//        }
//
//        // 查询数据
//        $data = (new SecretKey())::where('uuid', $uuid)
//            ->get([
//                'aes_key', 'aes_iv'
//            ])->toArray();
//
//        // 写入缓存
//        self::redis()->hMset($key, $data);

        return RedisService::hGetAllTable($key, $renew);
    }

    public function rsaKey(string $uuid, bool $renew = true): array
    {
        // 获取缓存
        $key = RedisKeys::SECRET_KEY_RSA . $uuid;

        return RedisService::hGetAllTable($key, $renew);
    }
}
