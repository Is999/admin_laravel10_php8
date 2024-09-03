<?php

namespace App\Http\Controllers;

use App\Enum\CheckMfaScenarios;
use App\Enum\Code;
use App\Enum\ConfigUuid;
use App\Enum\LogChannel;
use App\Enum\UserAction;
use App\Enum\UserMfaStatus;
use App\Exceptions\CustomizeException;
use App\Http\Validators\UserValidation;
use App\Logging\Logger;
use App\Models\User;
use App\Services\ConfigService;
use App\Services\IpService;
use App\Services\ResponseService as Response;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class UserController extends Controller
{

    /**
     * 登录
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->login($request);

            // 校验验证码
            if (!captcha_api_check($input["captcha"], $input["key"])) {
                throw new CustomizeException(Code::E100014, "验证码错误");
            }

            // 验证ip
            $ip = $request->getClientIp();
            if (true !== ConfigService::getCache(ConfigUuid::ADMIN_IP_WHITELIST_DISABLE)) {
                $whitelist = ConfigService::getCache(ConfigUuid::ADMIN_IP_WHITELIST);
                if ($whitelist && is_array($whitelist) && !in_array($ip, $whitelist)) {
                    throw new CustomizeException(Code::E100058);
                }
            }

            $name = $input['name'];
            $password = $input['password'];

            // 校验登录
            $userService = new UserService;
            $user = $userService->userCheck($name, $password);

            // 更新登录信息
            $update = [
                'last_login_time' => Carbon::now(),
                'last_login_ip' => $ip,
                'last_login_ipaddr' => IpService::getIpAddr($ip)
            ];

            if (!$user->update($update)) {
                throw new CustomizeException(Code::E100056);
            }

            // 更新缓存
            $userService->cacheUserInfo($user);

            // 记录操作日志
            $this->setUserLogByUid($user->id); // 设置日志用户id
            $this->addUserLog(__FUNCTION__, UserAction::LOGIN);

            // 过滤敏感字段
            $userInfo = Arr::except($user->toArray(), ['password', 'mfa_secure_key']);

            // MFA设备信息
            $userInfo = array_merge($userInfo, $userService->getUserMfaInfo($user->id, CheckMfaScenarios::LOGIN->value));

            //return Response::success(['user' => $userInfo, 'token' => $userService->generateToken($user)]);
            // 返回加密数据
            return Response::success(['user' => $userInfo, 'token' => $userService->generateToken($user)])->header('X-Cipher', base64_encode(json_encode(['json:user'])));
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 绑定安全码验证账号密码
     * @param Request $request
     * @return JsonResponse
     */
//    public function buildSecretVerifyAccount(Request $request): JsonResponse
//    {
//        try {
//            // 验证参数
//            $validator = Validator::make($request->input()
//                , [
//                    'name' => 'required|string|min:6|max:32',
//                    'password' => 'required',
//                    'key' => 'required|string',
//                    'captcha' => 'required|string|min:1|max:5', // 验证码
//                ]);
//
//            if ($validator->fails()) {
//                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
//            }
//
//            $input = $validator->validated();
//
//            // 校验验证码
//            if (!captcha_api_check($input["captcha"], $input["key"])) {
//                throw new CustomizeException(Code::E100014, "验证码错误");
//            }
//
//            // 验证ip
//            $ip = $request->getClientIp();
//            if (true !== ConfigService::getCache(ConfigUuid::ADMIN_IP_WHITELIST_DISABLE)) {
//                $whitelist = ConfigService::getCache(ConfigUuid::ADMIN_IP_WHITELIST);
//                if ($whitelist && !in_array($ip, $whitelist)) {
//                    throw new CustomizeException(Code::E100058);
//                }
//            }
//
//            $name = $input['name'];
//            $password = $input['password'];
//
//            // 校验登录
//            $userService = new UserService;
//            $user = $userService->userCheck($request, $name, $password);
//
//            if ($user->mfa_secure_key) {
//                $userInfo['exist_mfa'] = true;
//            } else {
//                $userInfo['exist_mfa'] = false;
//            }
//            $sign = $userService->generateSign(['id' => $user->id, 'name' => $user->name]);
//            $userInfo['build_mfa_url'] = "/mfa/secret/$sign";
//
//            // 更新登录信息
//            /*$ip = $request->getClientIp();
//            $update = [
//                'last_login_time' => Carbon::now(),
//                'last_login_ip' => $ip,
//                'last_login_ipaddr' => IpService::getIpAddr($ip)
//            ];
//
//            if (!$user->update($update)) {
//                throw new CustomizeException(Code::E100056);
//            }
//            // 更新缓存
//            UserService::cacheUserInfo($user);*/
//
//
//            // 记录操作日志
//            $this->setUserLogByUid($user->id); // 设置日志用户id
//            $this->addUserLog(__FUNCTION__, UserAction::BUILD_SECRET_VERIFY_ACCOUNT);
//
//            // 过滤敏感字段
//            $userInfo = array_merge($userInfo, Arr::except($user->toArray(), ['password', 'mfa_secure_key']));
//            return Response::success(['user' => $userInfo]);
//        } catch (CustomizeException $e) {
//            return Response::fail($e->getCode(), $e->getMessage());
//        } catch (Throwable $e) {
//            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
//            $this->systemException(__METHOD__, $e);
//            return Response::fail(Code::SYSTEM_ERR);
//        }
//    }

    /**
     * 用户权限uuid控制
     * @param Request $request
     * @return JsonResponse
     */
    public function permissions(Request $request): JsonResponse
    {
        try {
            // 获取该管理员权限uuid
            $list = (new UserService)->getUserPermissionUuid($request->offsetGet('user.id'));
            return Response::success($list);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 用户角色
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function roleList(Request $request, $id): JsonResponse
    {
        try {
            // 获取用户角色信息列表
            $admin = $request->offsetGet('user.id');
            $list = (new UserService)->userRoleList($admin, $id);
            return Response::success($list);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 给用户分配角色
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function editRole(Request $request, $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->editRole($request);

            $admin = $request->offsetGet('user.id');
            $res = (new UserService)->userEditRole($admin, $id, $input);
            if (!$res) {
                throw new CustomizeException(Code::F2003);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_ROLES_USER, "user.id=" . $id, $input);

            return Response::success([], Code::S1003);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 给用户分配角色
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function addRole(Request $request, $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->addRole($request);

            $admin = $request->offsetGet('user.id');
            $res = (new UserService)->userAddRole($admin, $id, $input);
            if (!$res) {
                throw new CustomizeException(Code::F2000);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::ADD_ROLES_USER, "user.id=" . $id, $input);
            return Response::success([], Code::S1000);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 解除角色与用户的关系
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function delRole(Request $request, $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->delRole($request);

            $admin = $request->offsetGet('user.id');
            $res = (new UserService)->userDelRole($admin, $id, $input);
            if (!$res) {
                throw new CustomizeException(Code::F2002);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::DEL_ROLES_USER, 'user.id=' . $id, $input);
            return Response::success([], Code::S1002);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 角色下拉列表
     * @param Request $request
     * @return JsonResponse
     */
    public function roleTreeList(Request $request): JsonResponse
    {
        try {
            $admin = $request->offsetGet('user.id');
            $result = (new UserService)->userRoleTreeList($admin);
            return Response::success($result);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail();
        }
    }

    /**
     * 账号角色
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function role(Request $request, $id): JsonResponse
    {
        try {
            $result = (new UserService)->userRole($id);
            return Response::success($result);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail();
        }
    }

    /**
     * 登出
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::LOGOUT);

            // 清除缓存
            (new UserService)->clearUserInfo($request->offsetGet('user.id'));

            return Response::success();
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 校验安全码
     * @param Request $request
     * @return JsonResponse
     */
    public function checkSecure(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->checkSecure($request);

            // 校验安全码
            $isOk = (new UserService)->checkSecure($request->offsetGet('user.id'), $input['secure']);
            return Response::success(['isOk' => $isOk]);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 校验MFA动态密码
     * @param Request $request
     * @return JsonResponse
     */
    public function checkMfaSecure(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->checkMfaSecure($request);

            $adminId = $request->offsetGet('user.id');
            $userService = new UserService;
            // 校验安全码
            $isOk = $userService->checkMfaSecure($adminId, $input['secure']);
            $info = $userService->getUserMfaInfo($adminId, $input['scenarios']);
            $twoStep = [];
            if ($isOk) {
                // 登录场景, 设置检验标识
                if (CheckMfaScenarios::LOGIN->value == $input['scenarios']) {
                    $userService->setLoginCheckMfaFlag($adminId);
                }

                // 是否有频率设置
                $times = intval($info['frequency']);
                $Key = $input['scenarios'];
                $expire = 300; // 5分钟
                if ($times > 0) {
                    $Key = CheckMfaScenarios::LOGIN->value;
                    $expire = $times;
                }

                // 存储两步校验码时间5分钟FREQUENCY
                $twoStep = $userService->setTwoStepCode($adminId, $Key, $expire);
            }

            return Response::success(array_merge($info, ['isOk' => $isOk, 'twoStep' => $twoStep]));
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 账号管理
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->index($request);

            // 查询数据
            $result = (new UserService)->list($input);
            return Response::success($result);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }


    /**
     * 个人信息
     * @param Request $request
     * @return JsonResponse
     */
    public function mine(Request $request): JsonResponse
    {
        try {
            // 过滤敏感字段
            $userService = new UserService;
            $user = $userService->getUserInfo($request->offsetGet('user.id'));
            $userInfo = Arr::except($user, ['password', 'mfa_secure_key']);

            $userInfo = array_merge($userInfo, $userService->getUserMfaInfo($user['id'], CheckMfaScenarios::LOGIN->value));

            // return Response::success($userInfo);
            // 返回加密数据
            return Response::success($userInfo)->header('X-Cipher', 'cipher');
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 修改密码
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->updatePassword($request);

            $userService = new UserService();
            // 验证身份: 两步校验，MFA设备开启的状态下，修改密码需先验证MFA设备。
            $mfaStatus = $request->offsetGet("user.mfa_status");
            if ($mfaStatus == UserMfaStatus::ENABLED->value && ConfigService::isCheckMfa(CheckMfaScenarios::CHANGE_PASSWORD->value)) {
                $adminId = $request->offsetGet("user.id");
                $key = $input['twoStepKey'];
                $value = $input['twoStepValue'];
                if ($userService->checkTwoStepCode($adminId, $key) !== $value) {
                    throw new CustomizeException(Code::F10006);
                }
            }

            $res = $userService->updatePassword(
                $request->offsetGet('user.id'),
                $input['passwordOld'],
                $input['passwordNew']
            );
            if (!$res) {
                return Response::fail(Code::F2001);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::UPDATE_PASSWORD);
            return Response::success([], Code::E100006);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 修改安全秘钥
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMfaSecureKey(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->updateMfaSecureKey($request);

            $userService = new UserService();
            // 验证身份: 两步校验，MFA设备开启的状态下，修改MFA设备秘钥需先验证旧MFA设备。
            $mfaStatus = $request->offsetGet("user.mfa_status");
            if ($mfaStatus == UserMfaStatus::ENABLED->value && ConfigService::isCheckMfa(CheckMfaScenarios::MFA_SECURE_KEY->value)) {
                $adminId = $request->offsetGet("user.id");
                $key = $input['twoStepKey'];
                $value = $input['twoStepValue'];
                if ($userService->checkTwoStepCode($adminId, $key) !== $value) {
                    throw new CustomizeException(Code::F10006);
                }
            }

            $result = $userService->editAccount($request->offsetGet('user.id'), $input);
            if (!$result) {
                return Response::fail(Code::F2001);
            }

            // 记录操作日志
            $input['mfa_secure_key'] = Crypt::encryptString($input['mfa_secure_key']);
            $this->addUserLog(__FUNCTION__, UserAction::UPDATE_MFA_SECURE_KEY, '', $input);

            return Response::success([], Code::S1001);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 添加账号
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->add($request);

            $adminId = $request->offsetGet('user.id');
            $userService = new UserService();
            // 验证身份: 两步校验
            if (ConfigService::isCheckMfa(CheckMfaScenarios::ADD_USER->value)) {
                $key = $input['twoStepKey'];
                $value = $input['twoStepValue'];
                if ($userService->checkTwoStepCode($adminId, $key) !== $value) {
                    throw new CustomizeException(Code::F10006);
                }
            }

            // 新增账号
            $result = $userService->addAccount($input);
            if (!$result) {
                throw new CustomizeException(Code::F2000);
            }

            // 过滤敏感字段
            $input = Arr::except($input, ['password', 'mfa_secure_key']);

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::ADD_USER, 'name=' . $input['name'], $input);
            return Response::success($result, Code::S1000);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }

    }

    /**
     * 编辑账号信息
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function edit(Request $request, $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->edit($request);

            $adminId = $request->offsetGet('user.id');
            $userService = new UserService();
            // 验证身份: 两步校验
            if (ConfigService::isCheckMfa(CheckMfaScenarios::EDIT_USER->value)) {
                $key = $input['twoStepKey'];
                $value = $input['twoStepValue'];
                if ($userService->checkTwoStepCode($adminId, $key) !== $value) {
                    throw new CustomizeException(Code::F10006);
                }
            }

            // 校验是否可以编辑状态
            if (Arr::get($input, 'status') !== null) {
                // 自己的数据不能修改
                if ($id == $adminId && Arr::get($input, 'status') != $request->offsetGet('user.status')) {
                    throw new CustomizeException(Code::E100059, ['param' => '状态']);
                }

                if ($id != $adminId) {
                    $userService->checkEditStatus($adminId, $id);
                }
            }

            // 编辑账号
            $result = $userService->editAccount($id, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2003);
            }

            // 过滤敏感字段
            $input = Arr::except($input, ['password', 'mfa_secure_key']);

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_USER, 'user.id=' . $id, $input);

            return Response::success([], Code::S1003);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }

    }

    /**
     * 更新基本信息(修改个人信息)
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMine(Request $request): JsonResponse
    {
        return $this->edit($request, $request->offsetGet('user.id'));
    }

    /**
     * 更换头像(修改个人信息)
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->updateAvatar($request);

            $adminId = $request->offsetGet('user.id');

            // 编辑账号
            $result = (new UserService)->editAccount($adminId, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2003);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_USER, 'user.id=' . $adminId, $input);

            return Response::success([], Code::S1003);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 修改MFA校验状态
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMfaStatus(Request $request): JsonResponse
    {
        return $this->editMfaStatus($request, $request->offsetGet('user.id'));
    }

    /**
     * 编辑状态
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function editStatus(Request $request, $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->editStatus($request);

            $adminId = $request->offsetGet('user.id');

            // 自己的状态不能修改
            if ($id == $adminId) {
                throw new CustomizeException(Code::E100059, ['param' => '状态']);
            }

            $userService = new UserService;

            // 验证身份: 两步校验
            if (ConfigService::isCheckMfa(CheckMfaScenarios::USER_STATUS->value)) {
                $key = $input['twoStepKey'];
                $value = $input['twoStepValue'];
                if ($userService->checkTwoStepCode($adminId, $key) !== $value) {
                    throw new CustomizeException(Code::F10006);
                }
            }

            // 非下级角色状态不能修改
            $userService->checkEditStatus($adminId, $id);

            $result = $userService->editAccount($id, $input);
            if (!$result) {
                throw new CustomizeException($request->input('status') ? Code::F2004 : Code::F2005);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_STATUS_USER, 'user.id=' . $id, $input);

            return Response::success([], $request->input('status') ? Code::S1004 : Code::S1005);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }


    /**
     * 编辑MFA校验状态
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function editMfaStatus(Request $request, $id): JsonResponse
    {
        try {
            // 验证参数
            $input = (new UserValidation())->editMfaStatus($request);

            $adminId = $request->offsetGet('user.id');
            $userService = new UserService;
            if ($id != $adminId) {
                // 非下级角色状态不能修改
                $userService->checkEditStatus($adminId, $id);
            }

            // 验证身份: 两步校验, 关闭身份验证器需先验证身份
            if ($input['mfa_status'] == UserMfaStatus::DISABLED->value && ConfigService::isCheckMfa(CheckMfaScenarios::MFA_STATUS->value)) {
                $key = $input['twoStepKey'];
                $value = $input['twoStepValue'];
                if ($userService->checkTwoStepCode($adminId, $key) !== $value) {
                    throw new CustomizeException(Code::F10006);
                }
            }

            $result = $userService->editAccount($id, $input);
            if (!$result) {
                throw new CustomizeException($request->input('mfa_status') ? Code::F2004 : Code::F2005);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_MFA_STATUS_USER, 'user.id=' . $id, $input);

            return Response::success([], $request->input('mfa_status') ? Code::S1004 : Code::S1005);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 获取绑定 TOTP MFA 设备秘钥的地址
     * 常用的基于时间的动态密码 (TOTP) 多重身份验证 (MFA)设备：如Google Authenticator、Microsoft Authenticator、Authing令牌、宁盾令牌等
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function buildMfaSecretKeyUrl(Request $request, $id): JsonResponse
    {
        try {
            // 验证参数
            $user = User::find($id);
            if (!$user) {
                throw new CustomizeException(Code::E100021);
            }

            $sign = (new UserService)->generateSign(['id' => $user->id, 'name' => $user->name]);
            $userInfo['build_mfa_url'] = "/mfa/secret/$sign";
            return Response::success($userInfo);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

}
