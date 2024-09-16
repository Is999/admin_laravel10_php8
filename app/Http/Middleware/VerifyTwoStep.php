<?php
/**
 * 对特定场景根据配置和参数进行两步验证
 */

namespace App\Http\Middleware;

use App\Enum\CheckMfaScenarios;
use App\Enum\Code;
use App\Enum\HttpStatus;
use App\Enum\LogChannel;
use App\Enum\UserMfaStatus;
use App\Exceptions\CustomizeException;
use App\Http\Validators\BaseValidation;
use App\Logging\Logger;
use App\Services\ConfigService;
use App\Services\ResponseService as Response;
use App\Services\UserService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RedisException;
use Throwable;

class VerifyTwoStep
{
    public function handle(Request $request, Closure $next, int $scenarios)
    {
        try {
            // 特殊场景满足某些条件才校验两步验证
            $check = true;
            switch ($scenarios) {
                // 修改密码，如果MFA设备状态是启用的则需校验两步验证
                case CheckMfaScenarios::CHANGE_PASSWORD->value:
                    // 修改MFA秘钥，如果MFA设备状态是启用的则需校验两步验证
                case CheckMfaScenarios::MFA_SECURE_KEY->value:
                    $check = $request->offsetGet("user.mfa_status") == UserMfaStatus::ENABLED->value;
                    break;
                // 修改MFA状态（关闭）则需校验两步验证
                case CheckMfaScenarios::MFA_STATUS->value:
                    $check = $request->input('mfa_status', 0) == UserMfaStatus::DISABLED->value;
                    break;
            }

            // 两步校验
            if ($check && ConfigService::isCheckMfa($scenarios)) {
                $this->checkTwoStepCode($request, $scenarios);
            }

            return $next($request);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, 'TwoStep', [
                'token' => $request->bearerToken(),
            ], $e);
            return Response::fail(Code::SYSTEM_ERR, null, HttpStatus::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 验证两步验证码
     * @param Request $request
     * @param int $scenarios
     * @return void
     * @throws CustomizeException
     * @throws RedisException
     * @throws ValidationException
     */
    public function checkTwoStepCode(Request $request, int $scenarios): void
    {
        // 验证参数
        $validator = Validator::make($request->input(), (new BaseValidation)->twoStepRule([$scenarios]));
        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        $input = $validator->validated();

        // 验证两步验证码
        $adminId = $request->offsetGet("user.id");
        $key = $input['twoStepKey'];
        $value = $input['twoStepValue'];
        if ((new UserService())->checkTwoStepCode($adminId, $key) !== $value) {
            throw new CustomizeException(Code::F10006);
        }
    }
}
