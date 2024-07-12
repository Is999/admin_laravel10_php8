<?php

namespace App\Models;

use App\Enum\ConfigType;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * @property int            $id         int unsigned auto_increment comment '主键'
 * @property string         $uuid       varchar(100) default ''                not null comment '配置唯一标识',
 * @property string         $title      varchar(30)  default ''                not null comment '配置标题',
 * @property ConfigType|int $type       tinyint      default 0                 not null comment '类型：0 String; 1 Integer; 2 Float; 3 Boolean；4 JsonArr; 5 仅做分组标题（配置归类）',
 * @property string         $value      text                                   not null comment '配置值',
 * @property string         $example    varchar(5000)                          not null comment '配置示例',
 * @property string         $remark     varchar(255) default ''                not null comment '备注',
 * @property string         $created_at timestamp    default CURRENT_TIMESTAMP not null comment '创建时间',
 * @property string         $updated_at timestamp    default CURRENT_TIMESTAMP null on update CURRENT_TIMESTAMP comment '更新时间',
 */
class Config extends Model
{
    /**
     * 与模型关联的数据表.
     *
     * @var string
     */
    protected $table = 'config';

    /**
     * Hash数据缓存: 权限 module
     * @param array $key
     * @param array $index
     * @return array
     */
    public function configsUuidHash(array $key = [], array $index = []): array
    {
        // 查询数据
        $list = self::when($key, function (Builder $query, $uuid) {
            return count($uuid) == 1 ? $query->where('uuid', $uuid[0]) : $query->whereIn('uuid', $uuid);
        })->orderBy('id')->get([
            'uuid', 'value', 'type'
        ])->toArray();

        $data = []; // key => value
        // 格式处理数据 key => value
        foreach ($list as $v) {
            $data[$v['uuid']] = ['value' => $v['value'], 'type' => $v['type']];
        }

        return $data;
    }
}
