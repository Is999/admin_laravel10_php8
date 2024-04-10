<?php

namespace App\Models;

use App\Services\AuthorizeService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permissions extends Model
{
    // 表名
    protected $table = 'permissions';

    /**
     * 上级权限
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'pid');
    }

    /**
     * 子级
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'pid', 'id');
    }

    /**
     * 递归查询子节点
     * @return HasMany
     */
    public function childRecursion(): HasMany
    {
        return $this->hasMany(self::class, 'pid', 'id')
            ->select([
                'id', 'uuid', 'title', 'module', 'pid', 'pids', 'type', 'describe', 'created_at', 'updated_at'
            ])
            ->with(['childRecursion']);
    }

    /**
     * Hash数据缓存: 权限 module
     * @param array $key
     * @param array $index
     * @return array
     */
    public function permissionsModuleHash(array $key = [], array $index = []): array
    {
        $key = array_merge($key, $index);
        // 查询数据
        $list = self::when($key, function ($query, $id) {
            return count($id) == 1 ? $query->where('id', $id[0]) : $query->whereIn('id', $id);
        })->orderBy('id')->get([
            'id', 'module'
        ])->toArray();

        $data = []; // key => value
        // 格式处理数据 key => value
        foreach ($list as $v) {
            $data[$v['id']] = $v['module'];
        }

        return $data;
    }

    /**
     * Hash数据缓存: 权限 uuid
     * @param array $key
     * @param array $index
     * @return array
     */
    public function permissionsUuidHash(array $key = [], array $index = []): array
    {
        $key = array_merge($key, $index);
        // 查询数据
        $list = self::when($key, function ($query, $id) {
            return count($id) == 1 ? $query->where('id', $id[0]) : $query->whereIn('id', $id);
        })->orderBy('id')->get([
            'id', 'uuid'
        ])->toArray();

        $data = []; // key => value
        // 格式处理数据 key => value
        foreach ($list as $v) {
            $data[$v['id']] = $v['uuid'];
        }

        return $data;
    }

    /**
     * String数据缓存: 权限
     * @param array $key
     * @param array $index
     * @return array
     */
    public function permissionsTreeString(array $key = [], array $index = []): array
    {
        // $key = array_merge($key, $index);

        // 查询数据
        $permissions = self::with(['childRecursion'])
            ->where('pid', 0)
            ->orderBy('id')
            ->get([
                'id', 'uuid', 'title', 'module', 'pid', 'pids', 'type', 'describe', 'created_at', 'updated_at'
            ])->toArray();

        (new AuthorizeService)->permissionsToTree($permissions);
        return $permissions;
    }
}