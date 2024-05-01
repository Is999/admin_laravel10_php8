<?php

namespace App\Exceptions;

use App\Enum\Code;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Logging\Logger;
use App\Services\ResponseService as Response;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'email',
        'phone',
        'mfa_secure_key',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // 为应用程序注册异常处理回调: 记录日志，并返回错误消息
        $this->reportable(function (Throwable $e) {
            //记录日志
            Logger::error(LogChannel::DEFAULT, 'Handler error', [], $e);

            //返回错误消息
            return Response::fail(Code::SYSTEM_ERR, 'Internal Server Error', HttpStatus::INTERNAL_SERVER_ERROR);
        });
    }
}
