<?php

namespace App\Models;

use App\Enum\SecretKeyStatus;
use App\Services\SecretKeyService;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * @property int                 $id                     int unsigned auto_increment comment '主键'
 * @property string              $uuid                   varchar(64)  default ''                not null comment 'API KEY 唯一标识 ',
 * @property string              $title                  varchar(100)                           not null comment '标题',
 * @property string              $aes_key                varchar(500) default ''                not null comment 'AES KEY  长度：16, 24, 32位',
 * @property string              $aes_iv                 varchar(500) default ''                not null comment 'AES IV 长度：16位',
 * @property string              $rsa_public_key_user    varchar(500) default ''                not null comment '用户 RSA 公钥文件路径',
 * @property string              $rsa_public_key_server  varchar(500) default ''                not null comment '服务器 RSA 公钥文件路径',
 * @property string              $rsa_private_key_server varchar(500) default ''                not null comment '服务器 RSA 私钥文件路径',
 * @property SecretKeyStatus|int $status                 tinyint      default 1                 not null comment '1 启用， 0 禁用',
 * @property string              $remark                 varchar(255) default ''                not null comment '备注',
 * @property string              $created_at             timestamp    default CURRENT_TIMESTAMP not null comment '创建时间',
 * @property string              $updated_at             timestamp    default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP comment '修改时间',
 */
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
