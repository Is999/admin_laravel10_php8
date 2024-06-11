<?php

namespace App\Contracts;

interface Crypto
{
    /**
     * 数据加密
     * @param string $data 待加密数据
     * @return bool|string
     */
    public function encrypt(string $data): bool|string;

    /**
     * 数据解密
     * @param string $data 待加密数据
     * @return bool|string
     */
    public function decrypt(string $data): bool|string;

}