<?php
/**
 * 给请求和日志添加Request-Id
 */

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
        $requestId = $request->header('X-Request-Id');
        if (empty($requestId)) {
            $requestId = (string)Str::uuid();
            $request->headers->set('X-Request-Id', $requestId);
        }
        $context = [
            'request-id' => $requestId,
            'route' => $request->route()->getName()
        ];

        // 用户有登录, 将用户信息也添加到日志上下文
        // 用户信息在Auth 中间件里添加

        // 添加上下文 request-id
        Logger::withContext(LogChannel::DEFAULT, $context); // 系统默认日志
        Logger::withContext(LogChannel::DEV, $context); // 开发专用日志
        if (env('DB_LOG')) {
            Logger::withContext(LogChannel::SQL, $context); // sql记录
        }

        // 记录网关日志
        if (env('APP_GATEWAY_LOG')) {
            Logger::info(LogChannel::GATEWAY, $request->getMethod() . ' '
                . $request->route()->getName()
                . '[' . $request->path() . ']'
                , [
                    'request-id' => $requestId,
                    'app-id' => $request->header('X-App-Id'),
                    'token' => $request->bearerToken(),
                    'ip' => $request->ip(),
                    'headers' => $this->getHeaders($request),
                    'body' => $request->all()
                ]);
        }

        $response = $next($request);

        // 设置请求的X-Request-Id到响应
        $response->header('X-Request-Id', $requestId);

        return $response;
    }

    private function getHeaders(Request $request): array
    {
        $headers = [];
        $headers['User-Agent'] = $request->header('User-Agent');
        $headers['Host'] = $request->header('Host');
        $headers['Origin'] = $request->header('Origin');
        $headers['Referer'] = $request->header('Referer');
        $headers['Sec-Ch-Ua'] = $request->header('Sec-Ch-Ua');
        $headers['Sec-Ch-Ua-Mobile'] = $request->header('Sec-Ch-Ua-Mobile'); // 0 pc 1移动
        $headers['Sec-Ch-Ua-Platform'] = $request->header('Sec-Ch-Ua-Platform');
        $headers['Sec-Fetch-Site'] = $request->header('Sec-Fetch-Site');

        $headers['X-Cipher'] = $request->header('X-Cipher');

        return array_filter($headers, function ($v) {
            return $v !== null;
        });
    }
}
