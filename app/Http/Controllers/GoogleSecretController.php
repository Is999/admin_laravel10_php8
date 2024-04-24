<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Models\User;
use App\Services\ResponseService as Response;
use App\Services\UserService;
use Earnp\GoogleAuthenticator\Facades\GoogleAuthenticator;
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
            $createSecret = GoogleAuthenticator::CreateSecret();
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
                    return back()->with('msg', '请正确输入手机上google验证码 ！')->withInput();
                }

                $input = $validator->validated();
                $user = User::where(['id' => $input['id'], 'name' => $input['name']])->first();
                if ($user->id != $input['id'] || $user->name != $input['name']) {
                    throw new CustomizeException(Code::E100021);
                }

                /*if ($user->secure_key) {
                    throw new CustomizeException(Code::FAIL, '已经绑定过安全秘钥！');
                }*/

                // 验证验证码和密钥是否相同
                if (GoogleAuthenticator::CheckCode($input['google'], $input['onecode'])) {
                    $input['google'] = Crypt::encryptString($input['google']); // 加密存储
                    // 更新秘钥
                    $userService->buildSecureKey($request, $user->id, $input['google']);

                    // 记录操作日志
                    $this->addUserLog(__FUNCTION__, UserAction::BUILD_SECURE_KEY, "name={$input['name']}", $input);

                    // 登录认证场景：认证成功，执行认证操作
                    return back()->with('msg', '绑定成功, 请去登录页面登录！')->onlyInput();

                } else {
                    // 绑定场景：认证失败，返回重新绑定，刷新新的二维码
                    return back()->with('msg', '请正确输入手机上google验证码 ！')->withInput();
                }
            }

            // 您自定义的参数，随表单返回
            $parameter = [["name" => "id", "value" => $user->id], ["name" => "name", "value" => $user->name]];
            return view('login.google.google', ['createSecret' => $createSecret, "parameter" => $parameter]);
        } catch (CustomizeException $e) {
            return back()->with('msg', '【' . $e->getCode() . '】' . $e->getMessage())->withInput();
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
                return back()->with('msg', '请正确输入手机上google验证码 ！')->withInput();
            }

            $input = $validator->validated();
            $user = User::where(['id' => $input['id'], 'name' => $input['name']])->first();
            if (!$user) {
                throw new CustomizeException(Code::E100021);
            }

            /*if ($user->secure_key) {
                throw new CustomizeException(Code::FAIL, '已经绑定过安全秘钥！');
            }*/

            // 验证验证码和密钥是否相同
            if (GoogleAuthenticator::CheckCode($input['google'], $input['onecode'])) {
                $input['google'] = Crypt::encryptString($input['google']); // 加密存储
                // 更新秘钥
                (new UserService)->buildSecureKey($request, $user->id, $input['google']);

                // 记录操作日志
                $this->setUserLogByUid($user->id); // 设置日志用户id
                $this->addUserLog(__FUNCTION__, UserAction::BUILD_SECURE_KEY, "name={$input['name']}", $input);

                // 登录认证场景：认证成功，执行认证操作
                //return Redirect('/')->with('msg', '绑定成功, 请去登录页面登录！')->onlyInput();
                return back()->with('msg', '绑定成功, 请去登录页面登录！')->onlyInput();

            } else {
                // 绑定场景：认证失败，返回重新绑定，刷新新的二维码
                return back()->with('msg', '请正确输入手机上google验证码 ！')->withInput();
            }
        } catch (CustomizeException $e) {
            return back()->with('msg', '【' . $e->getCode() . '】' . $e->getMessage())->withInput();
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return back()->with('msg', '【' . Code::SYSTEM_ERR . '】系统异常')->withInput();
        }
    }
}