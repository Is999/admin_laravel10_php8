<?php

namespace App\Services;

use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\OrderBy;
use App\Enum\RedisKeys;
use App\Enum\SecretKeyStatus;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Models\SecretKey;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use OpenSSLAsymmetricKey;

class SecretKeyService extends Service
{
    const AES = 'AES';
    const RSA = 'RSA';
    const USER_PUBLIC_KEY = 'user_public_key';
    const SERVER_PUBLIC_KEY = 'server_public_key';
    const SERVER_PRIVATE_KEY = 'server_private_key';

    /**
     * AES key
     * @param string $appId
     * @return array
     * @throws CustomizeException
     */
    public function getAesKeyByRequestAppId(string $appId): array
    {
        $aes = $this->getSecretKey($appId, SecretKeyService::AES);
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
     * @param string $appId
     * @param string $keyType
     * @return OpenSSLAsymmetricKey
     * @throws CustomizeException
     */
    public function getRsaKeyByRequestAppId(string $appId, string $keyType): OpenSSLAsymmetricKey
    {
        $rsa = $this->getSecretKey($appId, SecretKeyService::RSA);
        if ($keyType == SecretKeyService::USER_PUBLIC_KEY || $keyType == SecretKeyService::SERVER_PUBLIC_KEY) {
            $filePath = storage_path($rsa[$keyType]);

            // 验证公钥文件是否存在
            if (!$rsa[$keyType] || !file_exists($filePath)) {
                Logger::error(LogChannel::DEV, '公钥文件不存在：' . $filePath);
                throw new CustomizeException(Code::F5000, ['flag' => '003']);
            }

            // 获取文件中的公钥
            $original = file_get_contents($filePath);
            if ($original === false) {
                Logger::error(LogChannel::DEV, '获取公钥文件内容失败：' . $filePath);
                throw new CustomizeException(Code::F5001, ['flag' => '003']);
            }

            // 验证公钥是否是可用的
            $publicKey = openssl_pkey_get_public(trim($original));
            if ($publicKey === false) {
                Logger::error(LogChannel::DEV, '公钥无效：' . $filePath, [
                    'original' => $original
                ]);
                throw new CustomizeException(Code::F5002, ['flag' => '003']);
            }
            return $publicKey;
        }

        if ($keyType == SecretKeyService::SERVER_PRIVATE_KEY) {
            $filePath = storage_path($rsa[$keyType]);

            // 验证用私钥文件是否存在
            if (!$rsa[$keyType] || !file_exists($filePath)) {
                Logger::error(LogChannel::DEV, '私钥文件文件不存在：' . $filePath);
                throw new CustomizeException(Code::F5000, ['flag' => '001']);
            }

            // 获取文件中的私钥
            $original = file_get_contents($filePath);
            if ($original === false) {
                Logger::error(LogChannel::DEV, '获取私钥文件内容失败：' . $filePath);
                throw new CustomizeException(Code::F5001, ['flag' => '001']);
            }

            // 验证私钥是否是可用的
            $privateKey = openssl_pkey_get_private(trim($original));
            if ($privateKey === false) {
                Logger::error(LogChannel::DEV, '私钥无效：' . $filePath, [
                    'original' => $original
                ]);
                throw new CustomizeException(Code::F5002, ['flag' => '001']);
            }
            return $privateKey;
        }

        throw new CustomizeException(Code::F5007, ['param' => $keyType]);
    }

    /**
     * 获取配置
     * @param string $appId
     * @param string $type
     * @return array
     * @throws CustomizeException
     */
    public function getSecretKey(string $appId, string $type): array
    {
        // 获取appId配置
        $keys = [];
        if ($type === SecretKeyService::AES) {
            $keys = $this->aesKey($appId);
        } elseif ($type === SecretKeyService::RSA) {
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
     * 去掉 RSA秘钥串 头尾标记和换行符
     * @param $pem
     * @return string
     */
    public function removePEMHeaders($pem): string
    {
        $key = preg_replace('/-----BEGIN.*?-----|-----END.*?-----/', '', $pem);
        return str_replace(["\n", " "], '', $key);
    }



    /**
     * 为 RSA 密钥串添加头尾标记
     * @param string $key 秘钥
     * @param string $keyType 秘钥类型
     * @return string
     */
    public function addPEMHeaders(string $key, string $keyType): string
    {
        $pem = chunk_split(trim($key), 64, "\n");
        if (strtolower($key) == 'public') {
            return "-----BEGIN RSA PRIVATE KEY-----\n" . $pem . "-----END RSA PRIVATE KEY-----";
        }
        return "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----";
    }

    /**
     * 秘钥管理列表
     * @param Request $request
     * @param array $input
     * @return array
     */
    public function list(Request $request, array $input): array
    {
        // 分页, 排序
        $orderByField = Arr::get($input, 'field', 'id'); // 排序字段
        $orderByType = OrderBy::getLabel(Arr::get($input, 'order')); // 排序方式
        $page = Arr::get($input, 'page', 1); // 页码
        $pageSize = Arr::get($input, 'pageSize', 10); // 每页条数

        $uuid = Arr::get($input, 'uuid'); // 唯一标识
        $title = Arr::get($input, 'title'); // 权限名称
        $status = Arr::get($input, 'status'); // 状态

        // 查询
        $query = SecretKey::when($uuid, function (Builder $query, $val) {
            return $query->where('uuid', $val);
        })->when($title, function (Builder $query, $val) {
            return $query->where('title', 'like', "%$val%");
        })->when($status !== null, function (Builder $query) use ($status) { // 状态
            return $query->where('status', $status);
        });

        // 总数
        $total = $query->count();

        // 排序,分页
        $items = $query->select([
            'id', 'uuid', 'title', 'aes_key', 'aes_iv', 'rsa_public_key_user', 'rsa_public_key_server', 'rsa_private_key_server', 'status', 'remark', 'created_at', 'updated_at'
        ])->orderBy($orderByField, $orderByType)
            ->offset($pageSize * ($page - 1))->limit($pageSize)->get();

        return ['total' => $total, 'items' => $items];
    }
}
