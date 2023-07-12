<?php

namespace App\Http\Middleware;

use App\Enum\Code;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Enum\UserStatus;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\AuthorizeService;
use App\Services\ResponseService as Response;
use App\Services\UserService;
use Closure;
use Illuminate\Http\Request;
use Throwable;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // 获取request token
            $token = $request->bearerToken();
            if (!$token) {
                throw new CustomizeException(Code::UNAUTHORIZED);
            }

            // 验证ip
            $ip = $request->getClientIp();

            // 验证token 获取用户id
            $uid = UserService::CheckToken($token, $ip);

            // 获取用户信息
            $user = UserService::getUserInfo($uid);

            // 获取用户失败抛出自定义异常
            if (empty($user)) {
                throw new CustomizeException(Code::E100021);
            }

            // 校验用户状态
            if ($user['status'] != UserStatus::ENABLED->value) {
                throw new CustomizeException(Code::E100061);
            }

            // 不受限制的接口(只验证token不验证权限)
            if (!$request->routeIs('unrestricted.*')) {
                // 受限的接口必须验证权限, 根据路由名称验证权限
                $routeName = $request->route()->getName();
                if (!AuthorizeService::check($uid, $routeName)) {
                    return Response::fail(Code::UNAUTHORIZED, null, HttpStatus::FORBIDDEN);
                }
            }

            // user信息
            $request->user = $user;

            // 将email添加到上下文信息
            Logger::withContext(LogChannel::DEV, ['name' => $user['name']]);

            return $next($request);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage(), HttpStatus::UNAUTHORIZED);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, '无效的授权', [
                'token' => $request->bearerToken(),
            ], $e);
            return Response::fail(Code::INVALID_AUTHORIZATION, null, HttpStatus::UNAUTHORIZED);
        }
    }
}
