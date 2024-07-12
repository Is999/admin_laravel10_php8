<?php

namespace App\Models;

use App\Enum\Delete;
use App\Enum\RoleStatus;
use App\Services\RolesService;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int            $id             int unsigned auto_increment comment '主键'
 * @property string         $title          char(100)      default ''                not null comment '角色名称',
 * @property int            $pid            int unsigned   default '0'               not null comment '父级ID',
 * @property string         $pids           varchar(500)   default ''                not null comment '父级ID(族谱)',
 * @property RoleStatus|int $status         tinyint        default 1                 not null comment '状态：1正常，0禁用',
 * @property string         $permissions_id varchar(15000) default ''                not null comment '拥有的权限id 用","隔开; (子级只能拥有父级拥有的权限)',
 * @property string         $describe       varchar(255)   default ''                not null comment '描述',
 * @property int            $is_delete      tinyint        default 0                 not null comment '是否删除: 1删除(关联有用户或下级角色不能删除)',
 * @property string         $created_at     timestamp      default CURRENT_TIMESTAMP not null comment '创建时间',
 * @property string         $updated_at     timestamp      default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP comment '修改时间'
 */
class Roles extends Model
{
    // 表名
    protected $table = 'roles';


    private static int $SUPER_ROLE = 1; // 超级管理员角色id

    /**
     * 超级管理员角色id
     * .env 配置 SUPER_ROLE
     * @return int
     */
    public static function getSuperRole(): int
    {
        return env('SUPER_ROLE', self::$SUPER_ROLE);
    }

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
            , ['id', '>', Roles::getSuperRole()] // 超级管理员角色
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
            , ['id', '>', Roles::getSuperRole()] // 超级管理员角色
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
        (new RolesService())->rolesToTree($roles);

        return $roles;
    }
}
