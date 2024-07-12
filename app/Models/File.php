<?php

namespace App\Models;

use App\Enum\FileStatus;

/**
 * @property int            $id         int unsigned auto_increment comment '主键'
 * @property string         $name       varchar(500) collate utf8mb3_general_ci default ''                not null comment '文件名',
 * @property string         $type       varchar(60) collate utf8mb3_general_ci  default ''                not null comment '文件类型',
 * @property int            $size       int unsigned                            default '0'               not null comment '文件大小',
 * @property FileStatus|int $status     tinyint                                 default 0                 not null comment '文件状态：0 未使用，1使用中，2 删除标记， 3 已删除',
 * @property string         $expiration timestamp                                                         null comment '文件过期时间，过期后删除',
 * @property string         $modTime    timestamp                               default CURRENT_TIMESTAMP not null comment '文件最近【修改/访问】时间',
 * @property string         $created_at timestamp                               default CURRENT_TIMESTAMP not null comment '创建时间，文件【创建/上传】时间',
 * @property string         $updated_at timestamp                               default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP comment '修改时间，文件修改时间'
 */
class File extends Model
{
    /**
     * 与模型关联的数据表.
     *
     * @var string
     */
    protected $table = 'file';

}
