<?php
/**
 * 验证用户token、ip、访问权限等相关信息
 */

namespace App\Http\Middleware;

use App\Enum\CheckMfaScenarios;
use App\Enum\Code;
use App\Enum\ConfigUuid;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Enum\UserMfaStatus;
use App\Enum\UserStatus;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\AuthorizeService;
use App\Services\ConfigService;
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
            $userService = new UserService;
            $uid = $userService->CheckToken($token, $ip);

            // 获取用户信息
            $user = $userService->getUserInfo($uid);

            // 获取用户失败抛出自定义异常
            if (empty($user)) {
                throw new CustomizeException(Code::E100021);
            }

            // 校验用户状态
            if ($user['status'] != UserStatus::ENABLED->value) {
                throw new CustomizeException(Code::E100061);
            }

            // ignore.* 只验证token不验证权限
            if (!$request->routeIs('ignore.*')) {
                // 验证MFA身份验证器是否验证过：验证开关，验证是否登录校验过
                $MFACheckEnable = ConfigService::getCache(ConfigUuid::MFA_CHECK_ENABLE);
                if (!$MFACheckEnable) {
                    $MFACheckEnable = $user['mfa_status'] == UserMfaStatus::ENABLED;
                }
                // 校验开启，开始校验登录验证状态
                if ($MFACheckEnable) {
                    $val = $userService->getLoginCheckMfaFlag($uid);
                    if (!$val || $val < strtotime($user['last_login_time'])) {
                        return Response::json(false, Code::CHECK_MFA_CODE, null, $userService->getUserMfaInfo($uid, CheckMfaScenarios::LOGIN->value));
                    }
                }

                // 受限的接口必须验证权限, 根据路由名称验证权限
                $routeName = $request->route()->getName();
                if (!(new AuthorizeService)->check($uid, $routeName)) {
                    return Response::fail(Code::UNAUTHORIZED, null, HttpStatus::FORBIDDEN);
                }
            }

            // user信息
            $request->offsetSet('user', $user);

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
