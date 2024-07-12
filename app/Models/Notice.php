<?php

namespace App\Models;

use App\Enum\NoticeType;

/**
 * @property int            $id                 int unsigned auto_increment comment '主键'
 * @property string         $title              varchar(255) default ''                not null comment '标题',
 * @property string         $content            tinytext                               not null comment '内容',
 * @property string         $data               text                                   not null comment '数据(以json格式保存读取数据)',
 * @property NoticeType|int $type               tinyint                                not null comment '类型',
 * @property int            $receiver_id        int unsigned default '0'               not null comment '接收者id',
 * @property int            $sender_id          int unsigned default '0'               not null comment '发送者id',
 * @property int            $sender_notice_id   int                                    not null comment '发送方回复id(notice.id)',
 * @property int            $receiver_notice_id int unsigned default '0'               not null comment '接收方回复id(notice.id)',
 * @property string         $read_time          timestamp                              null comment '阅读时间',
 * @property string         $created_at         timestamp    default CURRENT_TIMESTAMP not null comment '创建时间',
 * @property string         $updated_at         timestamp    default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP comment '修改时间'
 */
class Notice extends Model
{
    /**
     * 与模型关联的数据表.
     *
     * @var string
     */
    protected $table = 'notice';

}
