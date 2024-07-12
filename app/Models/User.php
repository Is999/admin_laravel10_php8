<?php

namespace App\Models;

use App\Enum\UserMfaStatus;
use App\Enum\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int               $id                int auto_increment comment '主键'
 * @property string            $name              varchar(20)      default ''                not null comment '用户账号',
 * @property string            $real_name         varchar(20)      default ''                not null comment '用户名',
 * @property string            $password          varchar(500)     default ''                not null comment '密码hash',
 * @property string            $email             varchar(500)     default ''                not null comment '邮箱',
 * @property string            $phone             varchar(500)     default ''                not null comment '电话',
 * @property string            $mfa_secure_key    varchar(500)     default ''                not null comment '基于时间的动态密码 (TOTP) 多重身份验证 (MFA) 秘钥：如Google Authenticator、Microsoft Authenticator',
 * @property UserMfaStatus|int $mfa_status        tinyint unsigned default '0'               not null comment '启用 TOTP MFA (两步验证 2FA)：0 不启用，1 启用',
 * @property UserStatus|int    $status            tinyint          default 1                 not null comment '账户状态: 1正常, 0禁用',
 * @property string            $avatar            varchar(255)     default ''                not null comment '头像',
 * @property string            $remark            varchar(255)     default ''                not null comment '简介备注',
 * @property string            $last_login_time   timestamp                                  not null comment '最后登录时间',
 * @property string            $last_login_ip     varchar(32)      default ''                not null comment '最后登录ip',
 * @property string            $last_login_ipaddr varchar(255)     default ''                not null comment '最后登录ip区域',
 * @property string            $created_at        timestamp        default CURRENT_TIMESTAMP not null comment '添加时间',
 * @property string            $updated_at        timestamp        default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP comment '修改时间',
 */
class User extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 与模型关联的数据表.
     *
     * @var string
     */
    protected $table = 'users';

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
