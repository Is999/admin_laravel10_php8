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

use App\Enum\MenuShortcut;
use App\Enum\MenuStatus;
use App\Services\MenuService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int              $id 主键
 * @property string           $permissions_uuid char(8)          default ''                not null comment '权限uuid(permissions.uuid)',
 * @property string           $title            varchar(100)     default ''                not null comment '菜单名称',
 * @property string           $title_lang       varchar(255)     default ''                not null comment '多语言key',
 * @property MenuStatus|int   $status           tinyint          default 1                 not null comment '状态: 1显示, 0隐藏',
 * @property int              $pid              int unsigned     default '0'               not null comment '父级ID',
 * @property string           $pids             varchar(500)     default ''                not null comment '父级ID(族谱)',
 * @property string           $component        varchar(255)     default ''                not null comment 'Vue 组件',
 * @property string           $path             varchar(255)     default ''                not null comment 'Vue 路由',
 * @property int              $type             tinyint unsigned default '0'               not null comment '类型: 0目录, 1菜单',
 * @property string           $icon             varchar(255)                               not null comment '图标',
 * @property int              $sort             int              default 0                 not null comment '排序',
 * @property MenuShortcut|int $is_shortcut      tinyint          default 0                 not null comment '是否快捷: 1是, 0否',
 * @property string           $describe       varchar(255)     default ''                not null comment '描述',
 * @property string           $created_at timestamp                               default CURRENT_TIMESTAMP not null comment '创建时间，文件【创建/上传】时间',
 * @property string           $updated_at timestamp                               default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP comment '修改时间，文件修改时间'
*/
class Menu extends Model
{
    // 表名
    protected $table = 'menu';

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
    public function menuTreeString(array $key = [], array $index = []): array
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

        (new MenuService)->menusToTree($menus);
        return $menus;
    }
}
