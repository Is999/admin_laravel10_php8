<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'real_name',
        'email',
        'phone',
        'password',
        'mfa_secure_key',
        'mfa_status',
        'status',
        'group_id',
        'department',
        'position',
        'last_login_time',
        'last_login_ip',
        'last_login_ipaddr',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
//        'password',
    ];

    /**
     * 获取角色
     */
    public function roles(): HasMany
    {
        return $this->hasMany(UserRolesAccess::class, 'user_id', 'id');
    }

}
