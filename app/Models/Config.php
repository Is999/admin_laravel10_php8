<?php

namespace App\Models;

use App\Enum\Delete;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function configsUuidHashTable(array $key = [], array $index = []): array
    {
        $key = array_merge($key, $index);

        // 查询数据
        $list = self::when($key, function ($query, $uuid) {
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