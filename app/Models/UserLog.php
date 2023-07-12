<?php

namespace App\Models;

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