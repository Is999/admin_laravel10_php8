<?php

namespace App\Services;

use App\Contracts\Crypto;
use App\Contracts\Signature;
use OpenSSLAsymmetricKey;

class RsaService extends Service implements Signature, Crypto
{
    private OpenSSLAsymmetricKey $key;
    private string|int $algorithm;

    /**
     * 注意：私钥解密、签名，公钥加密、验证签名。请注意传入的key是私钥还是公钥，这里不验证key的正确性，请确保key是可使用的，在外层先验证好key
     * openssl_pkey_get_public 验证公钥是否是可用的
     * openssl_pkey_get_private 验证私钥是否是可用的
     * @param OpenSSLAsymmetricKey $key
     * @param string|int $algorithm 签名、验证签名（算法 OPENSSL_ALGO_SHA1, OPENSSL_ALGO_SHA256, OPENSSL_ALGO_SHA512, ...）， 加密、解密（填充算法 OPENSSL_PKCS1_PADDING）
     */
    public function __construct(OpenSSLAsymmetricKey $key, string|int $algorithm = OPENSSL_ALGO_SHA256)
    {
        parent::__construct();
        $this->key = $key;
        $this->algorithm = $algorithm;
    }

    /**
     * 私钥签名
     * @param string $data
     * @return string
     */
    public function sign(string $data): string
    {
        $signature = false;
        openssl_sign($data, $signature, $this->key, $this->algorithm);
        if (!$signature) {
            return false;
        }
        return base64_encode($signature);
    }

    /**
     * 公钥验签
     * @param string $data
     * @param string $sign
     * @return bool
     */
    public function verify(string $data, string $sign): bool
    {
        return openssl_verify($data, base64_decode($sign), $this->key, $this->algorithm) === 1;
    }

    /**
     * 公钥加密
     * @param string $data
     * @return bool|string
     */
    public function encrypt(string $data): bool|string
    {
        $keyDetails = openssl_pkey_get_details($this->key);
        if (!$keyDetails) {
            return false;
        }
        $chunkSize = ($keyDetails['bits'] / 8) - 11; // bit key - 11 byte padding (using RSA_PKCS1_PADDING)

        $output = '';
        while ($data) {
            $chunk = substr($data, 0, $chunkSize);
            $data = substr($data, $chunkSize);

            if (!openssl_public_encrypt($chunk, $encryptedChunk, $this->key)) {
                return false;
            }
            $output .= $encryptedChunk;
        }
        return base64_encode($output);
    }

    /**
     * 私钥解密
     * @param string $data
     * @return bool|string
     */
    public function decrypt(string $data): bool|string
    {

        $keyDetails = openssl_pkey_get_details($this->key);
        if (!$keyDetails) {
            return false;
        }
        $chunkSize = $keyDetails['bits'] / 8;

        $encryptedText = base64_decode($data);
        $output = '';
        while ($encryptedText) {
            $chunk = substr($encryptedText, 0, $chunkSize);
            $encryptedText = substr($encryptedText, $chunkSize);

            if (!openssl_private_decrypt($chunk, $decryptedChunk, $this->key)) {
                return false;
            }
            $output .= $decryptedChunk;
        }
        return $output;
    }

}
