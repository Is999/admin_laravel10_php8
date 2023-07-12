<?php

namespace App\Models;

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
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * 角色
     */
    public function role()
    {
        return $this->hasOne(Roles::class, 'id', 'role_id');
    }
}