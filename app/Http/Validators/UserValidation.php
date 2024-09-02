<?php

namespace App\Http\Validators;

use App\Enum\CheckMfaScenarios;
use App\Enum\Code;
use App\Enum\UserMfaStatus;
use App\Enum\UserStatus;
use App\Exceptions\CustomizeException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class UserValidation extends BaseValidation
{
    /**
     * 登录验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function login(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'name' => 'required|string|min:6|max:32',
                'password' => 'required',
                'key' => 'required|string',
                'captcha' => 'required', // 验证码
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 编辑角色验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function editRole(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'roles' => 'required|array', // 角色id
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 添加角色验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function addRole(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'roleId' => 'required|integer|min:1', // 角色id
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 解除角色验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function delRole(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'user_roles_id' => 'required|integer|min:1', // 角色与用户关系 user_roles_access.id
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 验证安全码验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function checkSecure(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'secure' => 'required',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 验证MFA设备安全码验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function checkMfaSecure(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'secure' => 'required',
                'scenarios' => [ // 校验MFA设备应用场景
                    'required',
                    new Enum(CheckMfaScenarios::class),
                ]
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 账号管理列表验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function index(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'name' => 'string|max:100', // 账号
                'email' => 'string|email', // 邮箱
                'status' => [ // 排序方式
                    new Enum(UserStatus::class),
                ],
                'role' => 'integer|min:1', // 角色
            ]);

        // 排序规则
        $validator->addRules($this->orderByRule());
        // 分页参数
        $validator->addRules($this->limitRule());

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 修改密码验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function updatePassword(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'passwordOld' => [
                    'required', // 接收前端MD5后的密码
                    function (string $attribute, mixed $value, Closure $fail) {
                        if (!preg_match("/^[A-Za-z0-9]{32}$/i", $value)) {
                            $fail("$attribute 格式不正确");
                        }
                    },
                ],
                'passwordNew' => [
                    'required', // 接收前端MD5后的密码
                    function (string $attribute, mixed $value, Closure $fail) {
                        if (!preg_match("/^[A-Za-z0-9]{32}$/i", $value)) {
                            $fail("$attribute 格式不正确");
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

        // 开启的状态下修改秘钥需先验证MFA设备
        $mfaStatus = $request->offsetGet("user.mfa_status");
        if ($mfaStatus == UserMfaStatus::ENABLED->value) {
            $validator->addRules([
                'twoStepKey' => [
                    'required',
                    Rule::in([CheckMfaScenarios::LOGIN->value, CheckMfaScenarios::CHANGE_PASSWORD->value]), // 允许使用登录身份验证信息
                ],
                'twoStepValue' => [
                    'required',
                ],
            ]);
        }

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 修改MFA设备秘钥验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function updateMfaSecureKey(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input(), [
            'mfa_secure_key' => 'required|min:16', // 安全码
        ]);

        // 开启的状态下修改秘钥需先验证MFA设备
        $mfaStatus = $request->offsetGet("user.mfa_status");
        if ($mfaStatus == UserMfaStatus::ENABLED->value) {
            $validator->addRules([
                'twoStepKey' => [
                    'required',
                    Rule::in([CheckMfaScenarios::LOGIN->value, CheckMfaScenarios::MFA_SECURE_KEY->value]), // 允许使用登录身份验证信息
                ],
                'twoStepValue' => [
                    'required',
                ],
            ]);
        }

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 添加账号验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function add(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'name' => [
                    'required',
                    function (string $attribute, mixed $value, Closure $fail) {
                        if (!preg_match("/^[a-zA-Z0-9]{6,20}$/", $value)) {
                            $fail("$attribute 长度必须为6-20位的字母+数字组合");
                        }
                        $user = User::where(['name' => $value])->first();
                        if ($user) {
                            $fail("$attribute 已存在，请更换一个$attribute");
                        }
                    },
                ],
                'real_name' => 'required|min:2|max:20',
                'password' => [
                    'required', // 接收前端MD5后的密码
                    function (string $attribute, mixed $value, Closure $fail) {
                        if (!preg_match("/^[A-Za-z0-9]{32}$/i", $value)) {
                            $fail("$attribute 格式不正确");
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
                            $fail("$attribute 格式不正确");
                        }
                    },
                ],
                'status' => [
                    'required',
                    new Enum(UserStatus::class),
                ],
                'mfa_status' => [
                    'required',
                    new Enum(UserMfaStatus::class),
                ],
                'mfa_secure_key' => [
                    'string',
                    function (string $attribute, mixed $value, Closure $fail) {
                        if (!preg_match("/^[A-Za-z0-9]{16,32}$/", $value)) {
                            $fail("$attribute 格式不正确");
                        }
                    },
                ],
                'avatar' => 'string',
                'remark' => 'string',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 修改账号验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function edit(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'name' => [
                    'required',
                ],
                'real_name' => 'required|min:2|max:20',
                'password' => [
                    function (string $attribute, mixed $value, Closure $fail) {
                        if (!preg_match("/^[A-Za-z0-9]{32}$/i", $value)) {
                            $fail("$attribute 格式不正确");
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
                            $fail("$attribute 格式不正确");
                        }
                    },
                ],
                'status' => [
                    new Enum(UserStatus::class),
                ],
                'mfa_status' => [
                    new Enum(UserMfaStatus::class),
                ],
                'mfa_secure_key' => [
                    'string',
                    function (string $attribute, mixed $value, Closure $fail) {
                        if (!preg_match("/^[A-Za-z0-9]{16,32}$/", $value)) {
                            $fail("$attribute 格式不正确");
                        }
                    },
                ],
                'avatar' => 'string',
                'remark' => 'string',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 修改头像验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function updateAvatar(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'avatar' => 'required',
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * 修改账号状态验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function editStatus(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'status' => [
                    'required',
                    new Enum(UserStatus::class),
                ],
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }


    /**
     * 修改账号MFA设备状态验证规则
     * @param Request $request
     * @return array
     * @throws CustomizeException
     * @throws ValidationException
     */
    public function editMfaStatus(Request $request): array
    {
        // 验证参数
        $validator = Validator::make($request->input()
            , [
                'mfa_status' => [
                    'required',
                    new Enum(UserMfaStatus::class),
                ],
                'twoStepKey' => [
                    'required_if:mfa_status,' . UserMfaStatus::DISABLED->value,
                    Rule::in([CheckMfaScenarios::LOGIN->value, CheckMfaScenarios::MFA_STATUS->value]), // 允许使用登录身份验证信息
                ],
                'twoStepValue' => [
                    'required_if:mfa_status,' . UserMfaStatus::DISABLED->value,
                ],
            ]);

        if ($validator->fails()) {
            throw new CustomizeException(Code::FAIL, $validator->errors()->first());
        }

        return $validator->validated();
    }
}
