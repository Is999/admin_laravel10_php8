<?php

namespace App\Models;

use App\Enum\UserAction;

/**
 * @property int               $id         int unsigned auto_increment comment '主键'
 * @property int               $user_id    int unsigned default '0'               not null comment '用户id',
 * @property string            $user_name  varchar(20)  default ''                not null comment '用户账户',
 * @property UserAction|string $action     varchar(100) default '0'               not null comment '动作名称',
 * @property string            $route      varchar(255) default ''                not null comment '路由名称',
 * @property string            $method     varchar(255) default ''                not null comment '模块/类/方法',
 * @property string            $describe   varchar(255) default ''                not null comment '描述',
 * @property string            $data       text                                   null comment '操作数据',
 * @property string            $ip         varchar(64)  default ''                not null comment 'IP地址',
 * @property string            $ipaddr     varchar(100) default ''                not null comment 'ip地区信息',
 * @property string            $created_at timestamp    default CURRENT_TIMESTAMP not null comment '创建时间'
 */
class UserLog extends Model
{
    /**
     * 与模型关联的数据表.
     *
     * @var string
     */
    protected $table = 'user_log';

    /**
     * 指示模型是否主动维护时间戳。
     *
     * @var bool
     */
    public $timestamps = false;
}
