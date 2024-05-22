<?php

namespace App\Models;

use Illuminate\Contracts\Database\Query\Builder;

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