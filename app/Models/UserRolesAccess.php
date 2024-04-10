<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;

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
        return $this->hasOne(Roles::class, 'id', 'role_id');
    }
}