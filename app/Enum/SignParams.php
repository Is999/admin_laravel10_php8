<?php

namespace App\Enum;

enum SignParams
{
    const userLogin = [
        'request' => ['name', 'password', 'secureCode'],
//        'response' => [],
    ];
}