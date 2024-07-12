<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int    $id         int unsigned auto_increment comment '主键'
 * @property int    $user_id    int unsigned                        not null comment '用户id',
 * @property int    $role_id    int unsigned                        not null comment '角色id',
 * @property string $created_at timestamp default CURRENT_TIMESTAMP not null comment '创建时间',
 */
class UserRolesAccess extends Model
{
    /**
     * 与模型关联的数据表.
     *
     * @var string
     */
    protected $table = 'user_roles_access';

    /**
     * 指示模型是否主动维护时间戳。
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 用户
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * 角色
     */
    public function role(): HasOne
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }
}
