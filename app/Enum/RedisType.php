<?php

namespace App\Enum;

/**
 *  Redis数据类型
 */
enum RedisType: int
{
    case String = 1; // 字符串(String)
    case Set = 2; // 集合(Set)
    case List = 3; // 列表(List)
    case ZSet = 4; // 有序集合(Sorted Set)
    case Hash = 5; // 哈希(Hash)
}