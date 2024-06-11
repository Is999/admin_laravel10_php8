<?php

namespace App\Services;

use App\Contracts\Crypto;
use App\Contracts\Signature;

class AesService extends Service implements Signature, Crypto
{
    private string $key;
    private string $iv;
    private string|int $cipher_algo;

    public function __construct(string $key, string $iv, string $cipher_algo = 'AES-256-CBC')
    {
        $this->key = $key;
        $this->iv = $iv;
        $this->cipher_algo = $cipher_algo;
    }

    /**
     * 签名
     * @param string $data
     * @return bool|string
     */
    public function sign(string $data): bool|string
    {
        return aesEncrypt(hash("sha256", $data), $this->key, $this->iv, $this->cipher_algo);
    }

    /**
     * 验签
     * @param string $data
     * @param string $sign
     * @return bool
     */
    public function verify(string $data, string $sign): bool
    {
        return aesDecrypt($sign, $this->key, $this->iv, $this->cipher_algo) == hash("sha256", $data);
    }

    /**
     * 加密
     * @param string $data
     * @return bool|string
     */
    public function encrypt(string $data): bool|string
    {
        return aesEncrypt($data, $this->key, $this->iv, $this->cipher_algo);
    }

    /**
     * 解密
     * @param string $data
     * @return bool|string
     */
    public function decrypt(string $data): bool|string
    {
        return aesDecrypt($data, $this->key, $this->iv, $this->cipher_algo);
    }
}
