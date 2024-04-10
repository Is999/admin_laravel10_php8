<?php
/*
|-----------------------------------------------------------------------------------------------------------
| laravel-admin-cms [ 简单高效的开发插件系统 ]
|-----------------------------------------------------------------------------------------------------------
| Licensed ( MIT )
| ----------------------------------------------------------------------------------------------------------
| Copyright (c) 2020-2021 https://gitee.com/liaodeiy/laravel-admin-cms All rights reserved.
| ----------------------------------------------------------------------------------------------------------
| Author: 廖春贵 < liaodeity@gmail.com >
|-----------------------------------------------------------------------------------------------------------
*/

namespace App\Models;

use App\Services\AuthorizeService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menus extends Model
{
    // 表名
    protected $table = 'menus';

    // 可批量分配的属性
    protected $fillable = ['permissions_uuid', 'title', 'title_lang', 'status', 'pid', 'pids', 'component', 'path', 'type', 'icon', 'sort', 'is_shortcut', 'describe'];

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
            ->select([
                'id', 'permissions_uuid', 'title', 'title_lang', 'status', 'pid', 'pids', 'component', 'path', 'type', 'icon', 'sort', 'is_shortcut', 'describe', 'created_at', 'updated_at'
            ])
            ->orderBy('sort', 'desc')
            ->orderBy('id')
            ->with(['childRecursion']);
    }

    /**
     * String数据缓存: 菜单
     * @param array $key
     * @param array $index
     * @return array
     */
    public function menusTreeString(array $key = [], array $index = []): array
    {
        // $key = array_merge($key, $index);

        // 查询数据
        $menus = self::with(['childRecursion'])
            ->where([
                ['pid', 0]
            ])->orderBy('sort', 'desc')
            ->orderBy('id')
            ->get([
                'id', 'permissions_uuid', 'title', 'title_lang', 'status', 'pid', 'pids', 'component', 'path', 'type', 'icon', 'sort', 'is_shortcut', 'describe', 'created_at', 'updated_at'
            ])->toArray();

        (new AuthorizeService)->menusToTree($menus);
        return $menus;
    }
}
