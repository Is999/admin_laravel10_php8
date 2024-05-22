<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel implements Builder
{
    // 时间转换
    protected $casts = [
        'password' => 'hashed',
        'last_login_time' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 指示模型是否主动维护时间戳。
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * 为 array / JSON 序列化准备日期格式
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }


    /**
     * 表名
     * @param string $alias
     * @return string
     */
    public function tableName(string $alias = ''): string
    {
        if ($alias) {
            return $this->getTable() . ' AS ' . $alias;
        }
        return $this->getTable();
    }
}
