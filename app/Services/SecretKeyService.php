<?php

namespace App\Services;

use App\Enum\RedisKeys;
use App\Models\SecretKey;

class SecretKeyService extends Service
{
    public function aesKey(string $uuid, bool $renew = false): array
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

        return RedisService::hMGetTable($key, ['aes_key', 'aes_iv'], $renew);
    }

    public function rsaKey(string $uuid, bool $renew = false): array
    {
        // 获取缓存
        $key = RedisKeys::SECRET_KEY_RSA . $uuid;

        return RedisService::hMGetTable($key, ['aes_key', 'aes_iv'], $renew);
    }
}
