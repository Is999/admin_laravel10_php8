<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\RedisKeys;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Models\User;
use App\Services\RedisService;
use App\Services\ResponseService as Response;
use App\Services\UserService;
use Earnp\GoogleAuthenticator\GoogleAuthenticator;
use Earnp\GoogleAuthenticator\Librarys\GoogleAuthenticator as GoogleSecretAuthenticator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Throwable;

class GoogleSecretController extends Controller
{

    /**
     * 绑定安全秘钥
     * @param Request $request
     * @param string $sign
     * @return View|JsonResponse|RedirectResponse|Route
     */
    public function secret(Request $request, string $sign): View|JsonResponse|RedirectResponse|Route
    {
        try {
            $userService = new UserService;
            $sign = $userService->checkSign($sign);
            if (!$sign->id || !$sign->name) {
                return back()->with('msg', '参数有误，请返回登录页面重新进入！')->withInput();
            }

            $user = User::where(['id' => $sign->id, 'name' => $sign->name])->first();
            if (!$user) {
                throw new CustomizeException(Code::E100021);
            }

            // 创建谷歌验证码
            $createSecret = $this->redis()->get(RedisKeys::ADMIN_MFA_SECRET . $sign->id);
            $createSecret = json_decode($createSecret, true);
            if (!$createSecret) {
                // $createSecret = GoogleAuthenticator::CreateSecret();
                $createSecret = self::CreateSecret('Admin-' . $user->real_name);
                RedisService::set(RedisKeys::ADMIN_MFA_SECRET . $sign->id, json_encode($createSecret), 300);
            }
            $ttl = $this->redis()->ttl(RedisKeys::ADMIN_MFA_SECRET . $sign->id);

            if ($request->isMethod('post')) {
                // 验证参数
                $validator = Validator::make($request->input()
                    , [
                        'id' => 'required|integer',
                        'name' => 'required|string',
                        'google' => 'required|string',
                        'onecode' => 'required|string|size:6',
                    ]);
                if ($validator->fails()) {
                    return back()->with('msg', '请正确输入手机上的动态密码 ！')->withInput();
                }

                $input = $validator->validated();
                $user = User::where(['id' => $input['id'], 'name' => $input['name']])->first();
                if ($user->id != $input['id'] || $user->name != $input['name']) {
                    throw new CustomizeException(Code::E100021);
                }

                /*if ($user->mfa_secure_key) {
                    throw new CustomizeException(Code::FAIL, '已经绑定过安全秘钥！');
                }*/

                // 验证验证码和密钥是否相同
                if (GoogleAuthenticator::CheckCode($input['google'], $input['onecode'])) {
                    $input['google'] = Crypt::encryptString($input['google']); // 加密存储
                    // 更新秘钥
                    $userService->buildMfaSecureKey($request, $user->id, $input['google']);

                    // 记录操作日志
                    $this->addUserLog(__FUNCTION__, UserAction::BUILD_MFA_SECURE_KEY, "name={$input['name']}", $input);

                    // 绑定成功删除缓存
                    $this->redis()->del(RedisKeys::ADMIN_MFA_SECRET . $user->id);

                    // 登录认证场景：认证成功，执行认证操作
                    return back()->with('exist_mfa', true)->with('msg', '绑定成功, 可以去您的应用程序中使用了！')->onlyInput();

                } else {
                    // 绑定场景：认证失败，返回重新绑定，刷新新的二维码
                    return back()->with('msg', '请正确输入手机上的动态密码 ！')->withInput();
                }
            }

            // 您自定义的参数，随表单返回
            $parameter = [["name" => "id", "value" => $user->id], ["name" => "name", "value" => $user->name]];
            return view('mfa.mfa', ['createSecret' => $createSecret, "parameter" => $parameter, 'ttl' => $ttl]);
        } catch (CustomizeException $e) {
            return back()->with('error_mfa', true)->with('msg', '【' . $e->getCode() . '】' . $e->getMessage())->withInput();
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 绑定安全秘钥
     * @param Request $request
     * @return View|JsonResponse|RedirectResponse|Route
     */
    public function buildSecretKey(Request $request): View|JsonResponse|RedirectResponse|Route
    {
        try {
            // 验证参数
            $validator = Validator::make($request->input()
                , [
                    'id' => 'required|integer',
                    'name' => 'required|string',
                    'google' => 'required|string',
                    'onecode' => 'required|string|size:6',
                ]);
            if ($validator->fails()) {
                return back()->with('msg', '请正确输入手机上的动态密码 ！')->withInput();
            }


            $input = $validator->validated();
            $user = User::where(['id' => $input['id'], 'name' => $input['name']])->first();
            if (!$user) {
                throw new CustomizeException(Code::E100021);
            }

            /*if ($user->mfa_secure_key) {
                throw new CustomizeException(Code::FAIL, '已经绑定过安全秘钥！');
            }*/

            // 验证验证码和密钥是否相同
            if (GoogleAuthenticator::CheckCode($input['google'], $input['onecode'])) {
                $input['google'] = Crypt::encryptString($input['google']); // 加密存储
                // 更新秘钥
                (new UserService)->buildMfaSecureKey($request, $user->id, $input['google']);

                // 记录操作日志
                $this->setUserLogByUid($user->id); // 设置日志用户id
                $this->addUserLog(__FUNCTION__, UserAction::BUILD_MFA_SECURE_KEY, "name={$input['name']}", $input);

                // 登录认证场景：认证成功，执行认证操作
                //return Redirect('/')->with('msg', '绑定成功, 请去登录页面登录！')->onlyInput();

                // 绑定成功删除缓存
                $this->redis()->del(RedisKeys::ADMIN_MFA_SECRET . $user->id);

                return back()->with('exist_mfa', true)->with('msg', '绑定成功, 可以去您的应用程序中使用了！')->onlyInput();

            } else {
                // 绑定场景：认证失败，返回重新绑定，刷新新的二维码
                return back()->with('msg', '请正确输入手机上的动态密码 ！')->withInput();
            }
        } catch (CustomizeException $e) {
            return back()->with('error_mfa', true)->with('msg', '【' . $e->getCode() . '】' . $e->getMessage())->withInput();
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return back()->with('msg', '【' . Code::SYSTEM_ERR . '】系统异常')->withInput();
        }
    }


    /**
     * 创建验证器秘钥
     * @param string $name
     * @return array
     */
    public static function CreateSecret(string $name = 'Laravel-Admin'): array
    {
        $google = new GoogleSecretAuthenticator();
        $secret = $google->createSecret();//创建一个Secret
        $qrCodeUrl = 'otpauth://totp/' . urlencode($name) . '?secret=' . $secret;//二维码中填充的内容
        $googlesecret = array('secret' => $secret, 'codeurl' => $qrCodeUrl);
        return $googlesecret;
    }
}