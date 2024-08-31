<?php

namespace App\Exceptions;

use App\Services\ResponseService as Response;
use Exception;

class CustomizeException extends Exception
{

    /**
     * 注意：第一个参数code, 第二个message；
     * @param int $code
     * @param string|array|null $message message类型为null或array时将先检测语言包中是否有数据
     */
    public function __construct(int $code, mixed $message = null)
    {
        parent::__construct(Response::parseMessage($code, $message), $code);
    }
}
