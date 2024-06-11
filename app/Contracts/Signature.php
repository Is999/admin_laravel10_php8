<?php

namespace App\Contracts;

interface Signature
{
    /**
     * 数据签名
     * @param string $data 待签名数据
     * @return bool|string
     */
    public function sign(string $data): bool|string;

    /**
     * 数据验证签名
     * @param string $data 待验证签名数据
     * @param string $sign 签名串
     * @return bool
     */
    public function verify(string $data, string $sign): bool;

}