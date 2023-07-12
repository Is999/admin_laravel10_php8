<?php

namespace App\Http\Middleware;

use App\Enum\LogChannel;
use App\Logging\Logger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssignRequestId
{
    /**
     * 处理传入的请求。
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $requestId = $request->header('X-Request-Id', (string)Str::uuid());
        $context = [
            'request-id' => $requestId,
            'route' => $request->route()->getName()
        ];

        // 用户有登录, 将用户信息也添加到日志上下文
        // 用户信息在Auth 中间件里添加

        // 添加上下文 request-id
        Logger::withContext(LogChannel::DEFAULT, $context); // 系统默认日志
        Logger::withContext(LogChannel::DEV, $context); // 开发专用日志
        Logger::withContext(LogChannel::SQL, $context); // sql记录

        // 记录网关日志
        if (env('APP_GATEWAY_LOG')) {
            Logger::info(LogChannel::GATEWAY, $request->getMethod() . ' '
                . $request->route()->getName()
                . '[' . $request->path() . ']'
                , [
                    //'headers' => $request->headers->all(),
                    'request-id' => $requestId,
                    'token' => $request->bearerToken(),
                    'ip' => $request->ip(),
                    'body' => $request->all()
                ]);
        }

        return $next($request)->header('X-Request-Id', $requestId);
    }
}
