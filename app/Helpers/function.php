<?php

/**
 * AES 加密
 * @param string $data
 * @param string $key
 * @param string $iv
 * @param string $cipher_algo
 * @return bool|string
 */
function aesEncrypt(string $data, string $key, string $iv, string $cipher_algo = 'AES-256-CBC'): bool|string
{
    $encrypted = openssl_encrypt($data, $cipher_algo, $key, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false) {
        return false;
    }
    return base64_encode($encrypted);
}

/**
 * AES 解密
 * @param string $encryptedData
 * @param string $key
 * @param string $iv
 * @param string $cipher_algo
 * @return false|string
 */
function aesDecrypt(string $encryptedData, string $key, string $iv, string $cipher_algo = 'AES-256-CBC'): bool|string
{
    return openssl_decrypt(base64_decode($encryptedData), $cipher_algo, $key, OPENSSL_RAW_DATA, $iv);
}