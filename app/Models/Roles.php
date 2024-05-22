<?php

namespace App\Models;

use App\Enum\Delete;
use App\Enum\RoleStatus;
use App\Services\AuthorizeService;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Roles extends Model
{
    // 表名
    protected $table = 'roles';

    /**
     * 上级角色
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
            ->where('is_delete', Delete::NO)
            ->select([
                'id', 'title', 'pid', 'pids', 'status', 'permissions_id', 'describe', 'is_delete', 'created_at', 'updated_at'
            ])
            ->with(['childRecursion']);
    }

    /**
     * Set集合数据缓存: 角色权限
     * @param array $key
     * @param array $index
     * @return array
     */
    public static function rolesPermissionsSet(array $key = [], array $index = []): array
    {
        $key = array_merge($key, $index);

        // 查询数据
        $list = self::where([
            ['is_delete', Delete::NO]
            , ['status', RoleStatus::ENABLED]
            , ['id', '>', (new AuthorizeService)->getSuperRole()] // 超级管理员角色
        ])->when($key, function ($query, $id) {
            return count($id) == 1 ? $query->where('id', $id[0]) : $query->whereIn('id', $id);
        })->get([
            'id', 'permissions_id'
        ])->toArray();

        $data = []; // key => value
        // 格式处理数据 key => value
        foreach ($list as $v) {
            $data[$v['id']] = $v['permissions_id'] ? explode(',', $v['permissions_id']) : [];
        }

        return $data;
    }

    /**
     * Hash数据缓存: 角色状态
     * @param array $key
     * @param array $index
     * @return array
     */
    public function rolesStatusHash(array $key = [], array $index = []): array
    {
        $key = array_merge($key, $index);
        // 查询数据
        $list = self::where([
            ['is_delete', Delete::NO]
            , ['id', '>', (new AuthorizeService)->getSuperRole()] // 超级管理员角色
        ])->when($key, function (Builder $query, $id) {
            return count($id) == 1 ? $query->where('id', $id[0]) : $query->whereIn('id', $id);
        })->get([
            'id', 'status'
        ])->toArray();

        $data = []; // key => value
        // 格式处理数据 key => value
        foreach ($list as $v) {
            $data[$v['id']] = $v['status'];
        }

        return $data;
    }

    /**
     * String数据缓存: 角色
     * @param array $key
     * @param array $index
     * @return array
     */
    public static function roleTreeString(array $key = [], array $index = []): array
    {
        //$key = array_merge($key, $index);

        // 查询数据
        $roles = self::with(['childRecursion'])
            ->where('pid', 0)
            ->where('is_delete', Delete::NO)
            ->orderBy('id')
            ->get([
                'id', 'title', 'pid', 'pids', 'status', 'permissions_id', 'describe', 'is_delete', 'created_at', 'updated_at'
            ])->toArray();

        // 转换数据
        (new AuthorizeService)->rolesToTree($roles);

        return $roles;
    }
}