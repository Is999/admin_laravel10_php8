<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\ConfigUuid;
use App\Enum\LogChannel;
use App\Enum\OrderBy;
use App\Enum\UserAction;
use App\Enum\UserStatus;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Models\User;
use App\Services\AuthorizeService;
use App\Services\ConfigService;
use App\Services\IpService;
use App\Services\ResponseService as Response;
use App\Services\UserService;
use Carbon\Carbon;
use Closure;
use Earnp\GoogleAuthenticator\Facades\GoogleAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class UserController extends Controller
{

    /**
     * 登录
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // 验证参数
            $validator = Validator::make($request->all()
                , [
                    'name' => 'required|string|min:6|max:32',
                    'password' => 'required',
                    'key' => 'required|string',
                    'captcha' => 'required', // 验证码
                    'secureCode' => 'required|string|size:6' // 安全码
                ]);

            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            // 校验验证码
            if (true !== ConfigService::getCache(ConfigUuid::CAPTCHA_DISABLE) && !captcha_api_check($input["captcha"], $input["key"], 'default')) {
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
            $user = UserService::userCheck($request, $name, $password);

            // 校验安全验证码
            if (true !== ConfigService::getCache(ConfigUuid::SECURE_DISABLE)) {
                if ($user->secure_key) {
                    $secureCode = $input['secureCode'] ?? '';
                    if (!GoogleAuthenticator::CheckCode(Crypt::decryptString($user->secure_key), $secureCode)) {
                        throw new CustomizeException(Code::E100048);
                    }
                    $userInfo['is_build_secure_key'] = true;
                } else {
                    $userInfo['is_build_secure_key'] = false;
                }
            }
            $sign = UserService::generateSign(['id' => $user->id, 'name' => $user->name]);
            $userInfo['build_secure_key_url'] = "/google/secret/{$sign}";

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
            UserService::cacheUserInfo($user);

            // 记录操作日志
            $this->setUserLogByUid($user->id); // 设置日志用户id
            $this->addUserLog(__FUNCTION__, UserAction::LOGIN);

            // 过滤敏感字段
            $userInfo = array_merge($userInfo, Arr::except($user->toArray(), ['password', 'secure_key']));
            return Response::success(['user' => $userInfo, 'token' => UserService::generateToken($user)]);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
    public function buildSecretVerifyAccount(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->all()
                , [
                    'name' => 'required|string|min:6|max:32',
                    'password' => 'required',
                    'key' => 'required|string',
                    'captcha' => 'required|string|min:1|max:5', // 验证码
                ]);

            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            // 校验验证码
            if (true !== ConfigService::getCache(ConfigUuid::CAPTCHA_DISABLE) && !captcha_api_check($input["captcha"], $input["key"], 'default')) {
                throw new CustomizeException(Code::E100014, "验证码错误");
            }

            // 验证ip
            $ip = $request->getClientIp();
            if (true !== ConfigService::getCache(ConfigUuid::ADMIN_IP_WHITELIST_DISABLE)) {
                $whitelist = ConfigService::getCache(ConfigUuid::ADMIN_IP_WHITELIST);
                if ($whitelist && !in_array($ip, $whitelist)) {
                    throw new CustomizeException(Code::E100058);
                }
            }

            $name = $input['name'];
            $password = $input['password'];

            // 校验登录
            $user = UserService::userCheck($request, $name, $password);

            if ($user->secure_key) {
                $userInfo['is_build_secure_key'] = true;
            } else {
                $userInfo['is_build_secure_key'] = false;
            }
            $sign = UserService::generateSign(['id' => $user->id, 'name' => $user->name]);
            $userInfo['build_secure_key_url'] = "/google/secret/{$sign}";

            // 更新登录信息
            /*$ip = $request->getClientIp();
            $update = [
                'last_login_time' => Carbon::now(),
                'last_login_ip' => $ip,
                'last_login_ipaddr' => IpService::getIpAddr($ip)
            ];

            if (!$user->update($update)) {
                throw new CustomizeException(Code::E100056);
            }
            // 更新缓存
            UserService::cacheUserInfo($user);*/


            // 记录操作日志
            $this->setUserLogByUid($user->id); // 设置日志用户id
            $this->addUserLog(__FUNCTION__, UserAction::BUILD_SECRET_VERIFY_ACCOUNT);

            // 过滤敏感字段
            $userInfo = array_merge($userInfo, Arr::except($user->toArray(), ['password', 'secure_key']));
            return Response::success(['user' => $userInfo]);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 用户权限uuid控制
     * @param Request $request
     * @return JsonResponse
     */
    public function permissions(Request $request): JsonResponse
    {
        try {
            // 获取该管理员权限uuid
            $list = AuthorizeService::getUserPermissionUuid($request->user['id']);
            return Response::success($list);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
            $list = AuthorizeService::userRoleList($request, $id);
            return Response::success($list);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
    public function editRoles(Request $request, $id): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->all()
                , [
                    'roles' => 'required|array', // 角色id
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();
            $res = AuthorizeService::userEditRoles($request, $id, $input);
            if (!$res) {
                throw new CustomizeException(Code::F2003);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_ROLES_USER, "user.id=" . $id, $input);

            return Response::success([], Code::S1003);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
            $validator = Validator::make($request->all()
                , [
                    'roleId' => 'required|integer|min:1', // 角色id
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            $res = AuthorizeService::userAddRole($request, $id, $input);
            if (!$res) {
                throw new CustomizeException(Code::F2000);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::ADD_ROLES_USER, "user.id=" . $id, $input);
            return Response::success([], Code::S1000);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
            $validator = Validator::make($request->all()
                , [
                    'user_roles_id' => 'required|integer|min:1', // 角色与用户关系 user_roles_access.id
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();
            $res = AuthorizeService::userDelRole($request, $id, $input);
            if (!$res) {
                throw new CustomizeException(Code::F2002);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::DEL_ROLES_USER, 'user.id=' . $id, $input);
            return Response::success([], Code::S1002);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
            $result = AuthorizeService::userRoleTreeList($request);
            return Response::success($result);
        } catch (\Throwable $e) {
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
    public function roles(Request $request, $id): JsonResponse
    {
        try {
            $result = AuthorizeService::userRoles($request, $id);
            return Response::success($result);
        } catch (\Throwable $e) {
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
            UserService::clearUserInfo($request->user['id']);

            return Response::success([]);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 验证密码
     * @param Request $request
     * @return JsonResponse
     */
    public function checkPassword(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->all()
                , [
                    'password' => 'required',
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            // 校验密码
            $isOk = UserService::CheckPassword($request->user['id'], $validator->validated()['password']);
            return Response::success(['isOk' => $isOk]);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
            $validator = Validator::make($request->all()
                , [
                    'name' => 'string|max:100', // 账号
                    'email' => 'string|email', // 邮箱
                    'status' => [ // 排序方式
                        new Enum(UserStatus::class),
                    ],
                    'role' => 'integer|min:1', // 角色
                    'field' => 'string', // 排序字段
                    'order' => [ // 排序方式
                        new Enum(OrderBy::class),
                    ],
                    'page' => 'integer|min:1', // 页码
                    'pageSize' => 'integer|between:10,100', // 每页条数
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            // 查询数据
            $result = UserService::index($request, $validator->validated());
            return Response::success($result);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
            $user = UserService::getUserInfo($request->user['id']);
            $userInfo = Arr::except($user, ['password', 'secure_key']);

            $sign = UserService::generateSign(['id' => $user['id'], 'name' => $user['name']]);
            $userInfo['is_build_secure_key'] = $user['secure_key'] ? true : false;
            $userInfo['build_secure_key_url'] = "/google/secret/{$sign}";

            return Response::success($userInfo);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all()
                , [
                    'passwordOld' => [
                        'required', // 接收前端MD5后的密码
                        function (string $attribute, mixed $value, Closure $fail) {
                            if (!preg_match("/^[A-Za-z0-9]{32}$/i", $value)) {
                                $fail("{$attribute} 格式不正确");
                            }
                        },
                    ],
                    'passwordNew' => [
                        'required', // 接收前端MD5后的密码
                        function (string $attribute, mixed $value, Closure $fail) {
                            if (!preg_match("/^[A-Za-z0-9]{32}$/i", $value)) {
                                $fail("{$attribute} 格式不正确");
                            }
                        },
//                        Password::min(6) // 至少需要 6 个字符
//                        ->letters() // 至少需要一个字母
//                        ->mixedCase() // 至少需要一个大写字母和一个小写字母
//                        ->numbers() // 至少需要一个数字
//                        ->symbols() // 至少需要一个符号
//                        ->uncompromised(1) // 确保密码未泄露
                    ], // 新密码
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }
            $res = UserService::updatePassword(
                $request,
                $request->user['id'],
                $request->input('passwordOld'),
                $request->input('passwordNew')
            );
            if (!$res) {
                return Response::fail(Code::F2001);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::UPDATE_PASSWORD);
            return Response::success([], Code::E100006);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
    public function updateSecureKey(Request $request)
    {
        try {
            $validator = Validator::make($request->all()
                , [
                    'secure_key' => 'required|min:16', // 安全码
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            $result = UserService::editAccount($request, $request->user['id'], $input);
            if (!$result) {
                return Response::fail(Code::F2001);
            }

            // 记录操作日志
            $input['secure_key'] = Crypt::encryptString($input['secure_key']);
            $this->addUserLog(__FUNCTION__, UserAction::UPDATE_SECURE_KEY, '', $input);

            return Response::success([], Code::S1001);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
            $validator = Validator::make($request->all()
                , [
                    'name' => [
                        'required',
                        function (string $attribute, mixed $value, Closure $fail) {
                            if (!preg_match("/^[a-zA-Z0-9]{6,20}$/", $value)) {
                                $fail("{$attribute} 长度必须为6-20位的字母+数字组合");
                            }
                            $user = User::where(['name' => $value])->first();
                            if ($user) {
                                $fail("{$attribute} 已存在，请更换一个{$attribute}");
                            }
                        },
                    ],
                    'real_name' => 'required|min:2|max:20',
                    'password' => [
                        'required', // 接收前端MD5后的密码
                        function (string $attribute, mixed $value, Closure $fail) {
                            if (!preg_match("/^[A-Za-z0-9]{32}$/i", $value)) {
                                $fail("{$attribute} 格式不正确");
                            }
                        },
//                        Password::min(6) // 至少需要 6 个字符
//                        ->letters() // 至少需要一个字母
//                        ->mixedCase() // 至少需要一个大写字母和一个小写字母
//                        ->numbers() // 至少需要一个数字
//                        ->symbols() // 至少需要一个符号
//                        ->uncompromised(1) // 确保密码未泄露
                    ],
                    'email' => 'required|email',
                    'phone' => [
                        'required',
                        function (string $attribute, mixed $value, Closure $fail) {
                            if (!preg_match("/^1[3-9]\d{9}$/", $value)) {
                                $fail("{$attribute} 格式不正确");
                            }
                        },
                    ],
                    'status' => [
                        'required',
                        new Enum(UserStatus::class),
                    ],
                    'secure_key' => [
                        'string',
                        function (string $attribute, mixed $value, Closure $fail) {
                            if (!preg_match("/^[A-Za-z0-9]{16,32}$/", $value)) {
                                $fail("{$attribute} 格式不正确");
                            }
                        },
                    ],
                    'avatar' => 'string',
                    'remark' => 'string',
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();
            // 新增账号
            $result = UserService::addAccount($request, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2000);
            }

            // 过滤敏感字段
            $input = Arr::except($input, ['password', 'secure_key']);

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::ADD_USER, 'name=' . $input['name'], $input);
            return Response::success($result, Code::S1000);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
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
            $validator = Validator::make($request->all()
                , [
                    'name' => [
                        'required',
                    ],
                    'real_name' => 'required|min:2|max:20',
                    'password' => [
                        function (string $attribute, mixed $value, Closure $fail) {
                            if (!preg_match("/^[A-Za-z0-9]{32}$/i", $value)) {
                                $fail("{$attribute} 格式不正确");
                            }
                        },
//                        Password::min(6) // 至少需要 6 个字符
//                        ->letters() // 至少需要一个字母
//                        ->mixedCase() // 至少需要一个大写字母和一个小写字母
//                        ->numbers() // 至少需要一个数字
//                        ->symbols() // 至少需要一个符号
//                        ->uncompromised(1) // 确保密码未泄露
                    ],
                    'email' => 'required|email',
                    'phone' => [
                        'required',
                        function (string $attribute, mixed $value, Closure $fail) {
                            if (!preg_match("/^1[3-9]\d{9}$/", $value)) {
                                $fail("{$attribute} 格式不正确");
                            }
                        },
                    ],
                    'status' => [
                        new Enum(UserStatus::class),
                    ],
                    'secure_key' => [
                        'string',
                        function (string $attribute, mixed $value, Closure $fail) {
                            if (!preg_match("/^[A-Za-z0-9]{16,32}$/", $value)) {
                                $fail("{$attribute} 格式不正确");
                            }
                        },
                    ],
                    'avatar' => 'string',
                    'remark' => 'string',
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            // 校验是否可以编辑状态
            if (Arr::get($input, 'status', null) !== null) {
                $this->checkEditStatus($request, $id);
            }

            // 编辑账号
            $result = UserService::editAccount($request, $id, $input);
            if (!$result) {
                throw new CustomizeException(Code::F2003);
            }

            // 过滤敏感字段
            $input = Arr::except($input, ['password', 'secure_key']);

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_USER, 'user.id=' . $id, $input);

            return Response::success([], Code::S1003);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }

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
            $validator = Validator::make($request->all()
                , [
                    'status' => 'required|boolean',
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $isEnabled = $request->input('status', true);

            // 不能修改自己的状态
            $this->checkEditStatus($request, $id);

            $input = $validator->validated();
            $result = UserService::editAccount($request, $id, $input);
            if (!$result) {
                throw new CustomizeException($isEnabled ? Code::F2004 : Code::F2005);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::EDIT_STATUS_USER, 'user.id=' . $id, $input);

            return Response::success([], $isEnabled ? Code::S1004 : Code::S1005);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return void
     * @throws CustomizeException
     */
    private function checkEditStatus(Request $request, $id): void
    {
        if ($id == $request->user['id']) {
            throw new CustomizeException(Code::E100059);
        }

        // 获取当前用户的角色
        $roles = AuthorizeService::getUserRoles($request->user['id']);

        // 判断用户是否拥有超级管理员权限
        if (!in_array(AuthorizeService::getSuperRole(), $roles)) {
            // 不能修改非下线角色
            $roles = AuthorizeService::getUserRoles($id);
            if ($roles) {
                $isEdit = false;
                foreach ($roles as $role) {
                    $isEdit = AuthorizeService::checkUserHasChildRole($request->user['id'], $role);
                    if ($isEdit) {
                        break;
                    }
                }
                if (!$isEdit) {
                    throw new CustomizeException(Code::E100060);
                }
            }
        }
    }

    /**
     * 获取绑定安全秘钥的地址
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function buildSecretKeyUrl(Request $request, $id): JsonResponse
    {
        try {
            // 验证参数
            $user = User::find($id);
            if (!$user) {
                throw new CustomizeException(Code::E100021);
            }

            $sign = UserService::generateSign(['id' => $user->id, 'name' => $user->name]);
            $userInfo['build_secure_key_url'] = "/google/secret/{$sign}";
            return Response::success($userInfo);
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

}
