<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\CryptoData;
use App\Http\Middleware\Lang;
use App\Http\Middleware\SignatureData;
use App\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 全局中间件
        $middleware->append([
            TrimStrings::class,
        ]);

        // 分组api 中间件
        $middleware->api(prepend: [
            ThrottleRequests::class . ':api',
            AssignRequestId::class, // 请求和日志添加 request_id
            Lang::class, // 多语言
            CryptoData::class, // 响应和请求参数加密解密
            SignatureData::class, // 请求参数签名验证，响应参数签名
        ]);

        // 分组upload 中间件
        $middleware->appendToGroup('upload', [
            ThrottleRequests::class . ':upload',
            SubstituteBindings::class,
            AssignRequestId::class, // 请求和日志添加 request_id
            Lang::class, // 多语言
        ]);

        // 中间件别名
        $middleware->alias([
            'adminAuth' => AdminAuth::class, //添加Token和权限认证中间件
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
