<?php

namespace App\Services;

use App\Contracts\Signature;

class Md5SignatureService extends Service implements Signature
{
    /**
     * 签名
     * @param string $data
     * @return string
     */
    public function sign(string $data): string
    {
        return md5($data);
    }

    /**
     * 验签
     * @param string $data
     * @param string $sign
     * @return bool
     */
    public function verify(string $data, string $sign): bool
    {
        return md5($data) == $sign;
    }

}
