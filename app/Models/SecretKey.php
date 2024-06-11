<?php

namespace App\Models;

use App\Services\SecretKeyService;
use Illuminate\Contracts\Database\Query\Builder;

class SecretKey extends Model
{
    /**
     * 与模型关联的数据表.
     *
     * @var string
     */
    protected $table = 'secret_key';

    /**
     * Hash数据缓存: AES
     * @param array $key
     * @param array $index
     * @return array
     */
    public function secretKeyAESHash(array $key = [], array $index = []): array
    {
        if (empty($key)) {
            return []; // 阻止缓存全部
        }

        // 查询数据
        $list = self::when($key, function (Builder $query, $uuid) {
            return count($uuid) == 1 ? $query->where('uuid', $uuid[0]) : $query->whereIn('uuid', $uuid);
        })->orderBy('id')->get([
            'uuid', 'aes_key', 'aes_iv', 'status'
        ])->toArray();


        $data = []; // key => value
        // 格式处理数据 key => value
        foreach ($list as $v) {
            $data[$v['uuid']] = ['key' => $v['aes_key'], 'iv' => $v['aes_iv'], 'status' => $v['status']];
        }

        return $data;
    }

    /**
     * Hash数据缓存: RSA
     * @param array $key
     * @param array $index
     * @return array
     */
    public function secretKeyRSAHash(array $key = [], array $index = []): array
    {
        if (empty($key)) {
            return []; // 阻止缓存全部
        }

        // 查询数据
        $list = self::when($key, function ($query, $uuid) {
            return count($uuid) == 1 ? $query->where('uuid', $uuid[0]) : $query->whereIn('uuid', $uuid);
        })->orderBy('id')->get([
            'uuid', 'rsa_public_key_user', 'rsa_public_key_server', 'rsa_private_key_server', 'status'
        ])->toArray();


        $data = []; // key => value
        // 格式处理数据 key => value
        foreach ($list as $v) {
            $data[$v['uuid']] = [
                SecretKeyService::USER_PUBLIC_KEY => $v['rsa_public_key_user'],
                SecretKeyService::SERVER_PUBLIC_KEY => $v['rsa_public_key_server'],
                SecretKeyService::SERVER_PRIVATE_KEY => $v['rsa_private_key_server'],
                'status' => $v['status']
            ];
        }

        return $data;
    }
}