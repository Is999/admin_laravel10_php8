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