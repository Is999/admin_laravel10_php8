<?php

/**
 * 生成待签名的字符串
 * @param array $data 参与签名的参数数组
 * @return string 待签名的字符串
 */
function getSignStr(array $data): string
{
    //剔除sign
    unset($data['sign']);
    unset($data['appKey']);

    //排序
    ksort($data);

    $str = '';
    foreach ($data as $k => $v) {
        if($v !== ''){
            $str .= $k . '=' . $v . '&';
        }
    }

    return rtrim($str,'&');
}

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