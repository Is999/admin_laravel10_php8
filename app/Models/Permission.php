<?php

namespace App\Models;

use App\Enum\PermissionStatus;
use App\Enum\PermissionType;
use App\Services\PermissionService;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                  $id         int unsigned auto_increment comment '主键'
 * @property string               $uuid       char(8)          default ''                not null comment '唯一标识',
 * @property string               $title      varchar(100)     default ''                not null comment '权限名称',
 * @property string               $module     varchar(250)     default ''                not null comment '权限匹配模型(路由名称 | 控制器/方法)',
 * @property int                  $pid        int unsigned     default '0'               not null comment '父级ID',
 * @property string               $pids       varchar(500)     default ''                not null comment '父级ID(族谱)',
 * @property PermissionType|int   $type       tinyint          default 0                 not null comment '类型: 0查看, 1新增, 2修改, 3删除, 4目录, 5菜单, 6页面, 7按钮, 8其它',
 * @property string               $describe   varchar(255)     default ''                not null comment '描述',
 * @property PermissionStatus|int $status     tinyint unsigned default '1'               not null comment '状态：1 启用；0 禁用',
 * @property string               $created_at timestamp        default CURRENT_TIMESTAMP not null comment '创建时间',
 * @property string               $updated_at timestamp        default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP comment '修改时间',
 */
class Permission extends Model
{
    // 表名
    protected $table = 'permission';

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
                'id', 'uuid', 'title', 'module', 'pid', 'pids', 'type', 'status', 'describe', 'created_at', 'updated_at'
            ])
            ->with(['childRecursion']);
    }

    /**
     * Hash数据缓存: 权限 module
     * @param array $key
     * @param array $index
     * @return array
     */
    public function permissionModuleHash(array $key = [], array $index = []): array
    {
        $key = array_merge($key, $index);
        // 查询数据
        $list = self::when($key, function (Builder $query, $id) {
            return count($id) == 1 ? $query->where('id', $id[0]) : $query->whereIn('id', $id);
        })->where('status', 1)->orderBy('id')->get([
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
    public function permissionUuidHash(array $key = [], array $index = []): array
    {
        $key = array_merge($key, $index);
        // 查询数据
        $list = self::when($key, function (Builder $query, $id) {
            return count($id) == 1 ? $query->where('id', $id[0]) : $query->whereIn('id', $id);
        })->where('status', 1)->orderBy('id')->get([
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
    public function permissionTreeString(array $key = [], array $index = []): array
    {
        // $key = array_merge($key, $index);

        // 查询数据
        $permissions = self::with(['childRecursion'])
            ->where('pid', 0)
            ->orderBy('id')
            ->get([
                'id', 'uuid', 'title', 'module', 'pid', 'pids', 'type', 'status', 'describe', 'created_at', 'updated_at'
            ])->toArray();

        (new PermissionService)->permissionToTree($permissions);
        return $permissions;
    }
}
