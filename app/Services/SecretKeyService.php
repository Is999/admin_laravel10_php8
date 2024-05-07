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
     * AES key
     * @param Request $request
     * @return array
     * @throws CustomizeException
     */
    public function getAesKeyByRequestAppId(Request $request): array
    {
        $aes = $this->getSecretKey($request, 'AES');
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
     * RSA key 私钥签名，公钥验签
     * @param Request $request
     * @return array
     * @throws CustomizeException
     */
    public function getRsaKeyByRequestAppId(Request $request, bool $userPublicKey, bool $serverPrivateKey): array
    {
        $publicKey = null;
        $privateKey = null;
        $rsa = $this->getSecretKey($request, 'RSA');
        if ($userPublicKey) {
            $filePath = storage_path($rsa['user_public_key']);

            // 验证用户公钥文件是否存在
            if (!$rsa['user_public_key'] || !file_exists($filePath)) {
                Logger::error(LogChannel::DEV, '用户公钥文件不存在：' . $filePath);
                throw new CustomizeException(Code::F5000, ['flag' => '003']);
            }

            // 获取文件中的用户公钥
            $original = file_get_contents($filePath);
            if ($original === false) {
                Logger::error(LogChannel::DEV, '获取用户公钥文件内容失败：' . $filePath);
                throw new CustomizeException(Code::F5001, ['flag' => '003']);
            }

            // 验证用户公钥是否是可用的
            $publicKey = openssl_pkey_get_public(trim($original));
            if ($publicKey === false) {
                Logger::error(LogChannel::DEV, '用户公钥无效：' . $filePath, [
                    'original' => $original
                ]);
                throw new CustomizeException(Code::F5002, ['flag' => '003']);
            }
        }

        if ($serverPrivateKey) {
            $filePath = storage_path($rsa['server_private_key']);

            // 验证用服务器私钥文件是否存在
            if (!$rsa['server_private_key'] || !file_exists($filePath)) {
                Logger::error(LogChannel::DEV, '服务器私钥文件文件不存在：' . $filePath);
                throw new CustomizeException(Code::F5000, ['flag' => '001']);
            }

            // 获取文件中的服务器私钥
            $original = file_get_contents($filePath);
            if ($original === false) {
                Logger::error(LogChannel::DEV, '获取服务器私钥文件内容失败：' . $filePath);
                throw new CustomizeException(Code::F5001, ['flag' => '001']);
            }

            // 验证服务器私钥是否是可用的
            $privateKey = openssl_pkey_get_private(trim($original));
            if ($privateKey === false) {
                Logger::error(LogChannel::DEV, '服务器私钥无效：' . $filePath, [
                    'original' => $original
                ]);
                throw new CustomizeException(Code::F5002, ['flag' => '001']);
            }
        }

        return ['user_public_key' => $publicKey, 'server_private_key' => $privateKey];
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
        $appId = $request->header('X-App-Id', '');
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

    /**
     * 设置私钥
     * @return bool
     */
    private function setupPrivateKey($priKey)
    {
        $result = false;
        if (is_resource($priKey)) {
            $result = true;
        } else {
            $pem = chunk_split(trim($priKey), 64, "\n");
            $pem = "-----BEGIN RSA PRIVATE KEY-----\n" . $pem . "-----END RSA PRIVATE KEY-----\n";
            $priKey = openssl_pkey_get_private($pem);
            if ($priKey) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * 设置公钥
     * @return bool
     */
    private function setupChannelPublicKey($channelPubKey)
    {
        $result = false;
        if (is_resource($this->_channelPublicKey)) {
            $result = true;
        } else {
            $pem = chunk_split(trim($channelPubKey), 64, "\n");
            $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
            $this->_channelPublicKey = openssl_pkey_get_public($pem);
            if ($this->_channelPublicKey) {
                $result = true;
            }
        }
        return $result;
    }
}
