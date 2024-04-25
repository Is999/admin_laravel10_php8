<?php

/**
 * AES 加密
 * @param $data
 * @param $key
 * @param $iv
 * @return string
 */
function aesEncrypt($data, $key, $iv) {
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($encrypted);
}

/**
 * AES 解密
 * @param $encryptedData
 * @param $key
 * @param $iv
 * @return false|string
 */
function aesDecrypt($encryptedData, $key, $iv) {
    $decrypted = openssl_decrypt(base64_decode($encryptedData), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted;
}