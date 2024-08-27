<?php

namespace App\Services;

use App\Enum\Code;
use App\Enum\ConfigUuid;
use App\Enum\Delete;
use App\Enum\FileStatus;
use App\Enum\LogChannel;
use App\Enum\OrderBy;
use App\Enum\RedisKeys;
use App\Enum\RoleStatus;
use App\Enum\UserMfaStatus;
use App\Enum\UserStatus;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleAccess;
use Earnp\GoogleAuthenticator\GoogleAuthenticator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RedisException;
use stdClass;
use Throwable;

class UserService extends Service
{
    /**
     * 存储token
     * @param int $uid
     * @param string $value
     * @return bool
     * @throws RedisException
     */
    public function setTokenCache(int $uid, string $value): bool
    {
        return self::redis()->setex(self::$APP_NAME . RedisKeys::TOKEN . $uid, 3600, $value);
    }

    /**
     * 获取token
     * @param int $uid
     * @return mixed false|mixed|string
     * @throws RedisException
     */
    public function getTokenCache(int $uid): mixed
    {
        return self::redis()->get(self::$APP_NAME . RedisKeys::TOKEN . $uid);
    }

    /**
     * 更新Token 过期时间
     * @param int $uid
     * @param int $ttl
     * @return bool
     * @throws RedisException
     */
    public function renewTokenCache(int $uid, int $ttl = 3600): bool
    {
        return self::redis()->expire(self::$APP_NAME . RedisKeys::TOKEN . $uid, $ttl);
    }

    /**
     * 删除token
     * @param int $uid
     * @return int
     * @throws RedisException
     */
    public static function delTokenCache(int $uid): int
    {
        return self::redis()->del(self::$APP_NAME . RedisKeys::TOKEN . $uid);
    }

    /**
     * 存储用户信息
     * @param int $uid
     * @param array $userInfo
     * @return bool
     * @throws RedisException
     */
    public function setUserInfoCache(int $uid, array $userInfo): bool
    {
        $res = self::redis()->hMSet(self::$APP_NAME . RedisKeys::USERINFO . $uid, $userInfo);
        if ($res) {
            self::redis()->expire(self::$APP_NAME . RedisKeys::USERINFO . $uid, 3600 * 8);
        }
        return $res;
    }

    /**
     * 获取用户信息
     * @param int $uid
     * @param array $fields
     * @return array
     * @throws RedisException
     */
    public function getUserInfoCache(int $uid, array $fields = []): array
    {
        if (empty($fields)) {
            return self::redis()->hGetAll(self::$APP_NAME . RedisKeys::USERINFO . $uid);
        }
        return self::redis()->hMGet(self::$APP_NAME . RedisKeys::USERINFO . $uid, $fields);
    }

    /**
     * 是否存在用户信息
     * @param int $uid
     * @return bool|int
     * @throws RedisException
     */
    public function checkUserInfoCacheExists(int $uid): bool|int
    {
        return self::redis()->exists(self::$APP_NAME . RedisKeys::USERINFO . $uid);
    }


    /**
     * 删除用户信息
     * @param int $uid
     * @return int
     * @throws RedisException
     */
    public function delUserInfoCache(int $uid): int
    {
        return self::redis()->del(self::$APP_NAME . RedisKeys::USERINFO . $uid);
    }

    /**
     * 存储用户角色信息
     * @param int $uid
     * @param array $roles
     * @return bool
     * @throws RedisException
     */
    public function setUserRoleCache(int $uid, array $roles): bool
    {
        $key = self::$APP_NAME . RedisKeys::USER_ROLES . $uid;
        $res = self::redis()->sAddArray($key, $roles);
        if ($res) {
            self::redis()->expire($key, 3600 * 24);
        }
        return $res;
    }

    /**
     * 获取用户角色信息
     * @param int $uid
     * @return array
     * @throws RedisException
     */
    public static function getUserRoleCache(int $uid): array
    {
        return self::redis()->sMembers(self::$APP_NAME . RedisKeys::USER_ROLES . $uid);
    }

    /**
     * 是否存在用户角色信息
     * @param int $uid
     * @return bool|int
     * @throws RedisException
     */
    public function checkUserRoleCacheExists(int $uid): bool|int
    {
        return self::redis()->exists(self::$APP_NAME . RedisKeys::USER_ROLES . $uid);
    }

    /**
     * 删除用户角色信息
     * @param int $uid
     * @return int
     * @throws RedisException
     */
    public static function delUserRoleCache(int $uid): int
    {
        return self::redis()->del(self::$APP_NAME . RedisKeys::USER_ROLES . $uid);
    }


    /**
     * 生成token
     * @param User $user
     * @return string
     */
    public function generateToken(User $user): string
    {
        try {
            $now = time();
            $tokenArr = [
                'env' => env('APP_NAME'), //应用
                'now' => $now, //时间
                'exp' => $now + 3600 * 24, //过期时间
                'id' => $user->id, //当前用户id
                'last' => $user->last_login_time, //最后登录时间
                'ip' => $user->last_login_ip, //登录ip
            ];

            $token = JWT::encode($tokenArr, env('APP_KEY'), 'HS256');
            $this->setTokenCache($user->id, $token); //token 保存redis服务器
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, '生成token异常', [
                'id' => $user->id,
                'name' => $user->name,
            ], $e);
            return '';
        }

        return $token;
    }

    /**
     * 验证token
     * @param string $token
     * @param $ip
     * @return int
     * @throws CustomizeException|RedisException
     */
    public function checkToken(string $token, $ip): int
    {
        try {
            $jwt = JWT::decode($token, new Key(env('APP_KEY'), 'HS256'));
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, 'JWT::decode 解析token异常', [
                'token' => $token,
            ], $e);
            throw new CustomizeException(Code::E100017);
        }

        $uid = (int)$jwt->id;
        if ($uid <= 0) {
            throw new CustomizeException(Code::INVALID_AUTHORIZATION);
        }

        // 验证应用
        if ($jwt->env !== env('APP_NAME')) {
            throw new CustomizeException(Code::UNAUTHORIZED);
        }

        // 验证过期时间
        if ($jwt->exp < time()) {
            throw new CustomizeException(Code::E100016);
        }

        // 验证IP
        if (true !== ConfigService::getCache(ConfigUuid::ADMIN_IP_WHITELIST_DISABLE)) {
            // 验证IP是否变更
            if (false !== ConfigService::getCache(ConfigUuid::ADMIN_CHECK_CHANGE_IP) && $jwt->ip != $ip) {
                throw new CustomizeException(Code::E100057);
            }
            // 验证IP变更后验证是否在白名单
            if ($jwt->ip != $ip) {
                $whitelist = ConfigService::getCache(ConfigUuid::ADMIN_IP_WHITELIST);
                if ($whitelist && !in_array($ip, $whitelist)) {
                    throw new CustomizeException(Code::E100058);
                }
            }
        }

        // 验证服务器现有的token
        $temp_token = $this->getTokenCache($uid);
        if ($temp_token != $token) {
            throw new CustomizeException(Code::E100018);
        }

        $this->renewTokenCache($uid); //更新token过期时间

        return $uid;
    }

    /**
     * 简单签名（非RSA非对称签名）
     * @param array $arr
     * @param int $exp
     * @return string
     */
    public function generateSign(array $arr, int $exp = 60 * 20): string
    {
        try {
            $now = time();
            $singArr = [
                'env' => env('APP_NAME'), //应用
                'now' => $now, //时间
                'exp' => $now + $exp, //过期时间
            ];
            $singArr = array_merge($singArr, $arr);


            $token = JWT::encode($singArr, env('APP_KEY'), 'HS256');
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, '生成Sign异常', $arr, $e);
            return '';
        }

        return $token;
    }

    /**
     * 验证签名
     * @param string $sign
     * @return stdClass
     * @throws CustomizeException
     */
    public function checkSign(string $sign): stdClass
    {
        try {
            $jwt = JWT::decode($sign, new Key(env('APP_KEY'), 'HS256'));
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, 'JWT::decode 解析token异常', [
                'sign' => $sign,
            ], $e);
            throw new CustomizeException(Code::E100017);
        }

        // 验证应用
        if ($jwt->env !== env('APP_NAME')) {
            throw new CustomizeException(Code::UNAUTHORIZED);
        }

        // 验证过期时间
        if ($jwt->exp < time()) {
            throw new CustomizeException(Code::E100016);
        }

        return $jwt;
    }

    /**
     * 验证用户是否是超级管理员
     * @param int $uid
     * @return bool
     * @throws RedisException
     */
    public function checkUserIsSuperRole(int $uid): bool
    {
        // 获取用户的角色
        $roles = $this->getUserRole($uid);

        // 判断用户是否拥有超级管理员权限
        if (in_array(Role::getSuperRole(), $roles)) {
            return true;
        }

        return false;
    }


    /**
     * 验证用户【uid】是否有该角色
     * @param int $uid
     * @param int $roleId
     * @return bool
     * @throws RedisException
     */
    public function checkUserHasRole(int $uid, int $roleId): bool
    {
        // 获取用户的角色
        $roles = $this->getUserRole($uid);

        // 判断用户是否拥有超级管理员权限或者roleId
        if (in_array(Role::getSuperRole(), $roles) || in_array($roleId, $roles)) {
            return true;
        }

        // 未匹配到权限
        return false;
    }

    /**
     * 校验当前管理是否可以更改用户状态
     * @param int $adminId 管理员id
     * @param int $uid 用户id
     * @return void
     * @throws CustomizeException|RedisException
     */
    public function checkEditStatus(int $adminId, int $uid): void
    {
        // 判断用户是否拥有超级管理员权限
        if (!$this->checkUserIsSuperRole($adminId)) {
            // 不能修改非下线角色
            $roles = $this->getUserRole($uid);
            if ($roles) {
                foreach ($roles as $role) {
                    $isEdit = $this->checkUserHasChildRole($adminId, $role);
                    if (!$isEdit) {
                        $title = Role::where('id', $role)->value('title');
                        throw new CustomizeException(Code::E100060, ['role' => $title]);
                    }
                }
            }
        }
    }

    /**
     * 验证角色【roleId】是否是用户【uid】所拥有角色的子角色
     * @param int $uid
     * @param int $roleId
     * @return bool
     * @throws RedisException
     */
    public function checkUserHasChildRole(int $uid, int $roleId): bool
    {
        // 获取用户的角色
        $roles = $this->getUserRole($uid);

        // 判断用户是否拥有超级管理员权限
        if (in_array(Role::getSuperRole(), $roles)) {
            return true;
        }

        // 查角色族谱是否拥有该用户拥有的角色id
        $pids = Role::where('id', $roleId)->value('pids');
        if ($pids) {
            $pids = explode(',', $pids);
            foreach ($roles as $role) {
                if (in_array($role, $pids)) {
                    return true;
                }
            }
        }

        // 未匹配到权限
        return false;
    }

    /**
     * 获取用户权限uuid
     * @param int $uid
     * @return array
     * @throws RedisException
     */
    public function getUserPermissionUuid(int $uid): array
    {
        $data = [
            'superUserRole' => 0, // 是否是超级管理员
            // 'roles' => [], // 角色
            'permissions' => [] // 权限
        ];

        // 获取用户的角色
        $roles = $this->getUserRole($uid);

        $data['roles'] = $roles;

        // 判断用户是否拥有超级管理员权限
        if (in_array(Role::getSuperRole(), $roles)) {
            $data['superUserRole'] = 1;
            return $data;
        }

        $permissions = [];
        $rolesService = new RoleService;
        // 非超级管理员权限
        foreach ($roles as $roleId) {
            // 获取角色状态
            if ($rolesService->getRoleStatus($roleId) != RoleStatus::ENABLED->value) {
                continue;
            }

            // 获取角色权限id
            $permissions = array_merge($permissions, $rolesService->getRolePermission($roleId));
        }

        // 去重, 并获取uuid
        if ($permissions) {
            $data['permissions'] = (new PermissionService)->getPermissionUuid(array_unique($permissions, SORT_NUMERIC));
        }

        return $data;
    }

    /**
     * 获取用户信息
     * @param int $uid
     * @param array $fields
     * @return array
     * @throws RedisException
     */
    public function getUserInfo(int $uid, array $fields = []): array
    {
        $user = $this->getUserInfoCache($uid, $fields);
        if (empty($user)) {
            $user = User::find($uid)->toArray();
            $this->setUserInfoCache($uid, $user);
        }
        return $user;
    }

    /**
     * 登录验证
     * @param string $name
     * @param string $password
     * @return User
     * @throws CustomizeException
     */
    public function userCheck(string $name, string $password): User
    {
        $user = User::where('name', $name)->first();
        if (!$user) {
            throw new CustomizeException(Code::E100015);
        }

        // 验证状态
        if ((int)$user->status !== 1) {
            throw new CustomizeException(Code::E100020);
        }

        //dd(Hash::make($password . substr(md5($user->name), 10, 10)));
        // 验证密码
        if (!Hash::check($password . substr(md5($user->name), 10, 10), $user->password)) {
            throw new CustomizeException(Code::E100019);
        }

        return $user;
    }

    /**
     * 缓存用户信息
     * @param User $user
     * @return void
     * @throws RedisException
     */
    public function cacheUserInfo(User $user): void
    {
        // 缓存用户信息
        $this->setUserInfoCache($user->id, $user->toArray());

        // 更新用户角色缓存
        $this->getUserRole($user->id, true);
    }

    /**
     * 校验安全码或密码（没有开启安全码校验则校验密码）
     * @param int $id
     * @param string $secure
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function checkSecure(int $id, string $secure): bool
    {
        $user = $this->getUserInfo($id);
        if (!$user) {
            throw new CustomizeException(Code::E100015);
        }

        // 强检验
        $MFACheckEnable = ConfigService::getCache(ConfigUuid::MFA_CHECK_ENABLE);

        // 校验安全验证码
        if ($MFACheckEnable || UserMfaStatus::DISABLED->value != intval($user['mfa_status'])) {
            if ($user['mfa_secure_key']) {
                if (GoogleAuthenticator::CheckCode(Crypt::decryptString($user['mfa_secure_key']), $secure)) {
                    return true;
                }
                return false;
            }
        }

        // 验证密码
        if (Hash::check(md5($secure) . substr(md5($user['name']), 10, 10), $user['password'])) {
            return true;
        }

        return false;
    }

    /**
     * 校验MFA动态密码
     * @param int $id
     * @param string $secure
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function checkMfaSecure(int $id, string $secure): bool
    {
        $user = $this->getUserInfo($id);
        if (!$user) {
            throw new CustomizeException(Code::E100015);
        }

        // 强检验
        $MFACheckEnable = ConfigService::getCache(ConfigUuid::MFA_CHECK_ENABLE);

        // 校验安全验证码
        if ($MFACheckEnable || UserMfaStatus::DISABLED->value != intval($user['mfa_status'])) {
            if ($user['mfa_secure_key']) {
                if (GoogleAuthenticator::CheckCode(Crypt::decryptString($user['mfa_secure_key']), $secure)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * 添加账号
     * @param array $input
     * @return array
     * @throws CustomizeException
     */
    public function addAccount(array $input): array
    {
        // 身份验证器（基于时间的动态密码 (TOTP) 多重身份验证 (MFA)）秘钥：如 Google Authenticator、Microsoft Authenticator
        $mfa_secure_key = Arr::get($input, 'mfa_secure_key');
        $mfa_status = Arr::get($input, 'mfa_status', UserMfaStatus::DISABLED->value); // 启用 TOTP MFA (两步验证 2FA)：0 不启用，1 启用

        // 验证是否可以启用MFA校验
        if ($mfa_status == UserMfaStatus::ENABLED->value && empty($mfa_secure_key)) {
            throw new CustomizeException(Code::E100068);
        }

        $name = Arr::get($input, 'name'); // 账号
        if (User::where('name', $name)->exists()) {
            throw new CustomizeException(Code::E100047, ['name' => $name]);
        }

        $real_name = Arr::get($input, 'real_name', ''); // 真实姓名
        $password = Arr::get($input, 'password', Str::random(32)); // 密码
        $email = Arr::get($input, 'email', ''); // 邮箱
        $phone = Arr::get($input, 'phone', ''); // 邮箱
        $status = Arr::get($input, 'status', UserStatus::ENABLED); // 状态
        $avatar = Arr::get($input, 'avatar', ''); // 状态
        $remark = Arr::get($input, 'remark', ''); // 状态

        // 添加数据
        $model = new User();
        $model->name = $name;
        $model->real_name = $real_name;
        $model->password = Hash::make($password . substr(md5($name), 10, 10));
        $model->email = $email;
        $model->phone = $phone;
        $model->mfa_secure_key = $mfa_secure_key ? Crypt::encryptString($mfa_secure_key) : '';
        $model->mfa_status = $mfa_status;
        $model->status = $status;
        $model->avatar = $avatar;
        $model->remark = $remark;
        $model->last_login_time = date('Y-m-d H:i:s', 1);
        $model->last_login_ip = '';
        $model->last_login_ipaddr = '';
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');

        $res = $model->save();
        if (!$res) {
            throw new CustomizeException(Code::F2000);
        }

        // 文件处理：头像图片启用
        if ($avatar) {
            (new FileService)->updateStatus($avatar, FileStatus::USING);
        }

        return $model->toArray();
    }

    /**
     * 编辑账号
     * @param int $id
     * @param array $input
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function editAccount(int $id, array $input): bool
    {
        $password = Arr::get($input, 'password'); // 密码
        $mfa_secure_key = Arr::get($input, 'mfa_secure_key'); // 密码
        $mfa_status = Arr::get($input, 'mfa_status'); // 密码

        // 获取要编辑的账号
        $model = User::find($id);
        if (!$model) {
            throw new CustomizeException(Code::E100053);
        }

        // 更新字段重新赋值
        $model->real_name = Arr::get($input, 'real_name', $model->real_name); // 用户名
        $model->password = $password ? Hash::make($password . substr(md5($model->name), 10, 10)) : $model->password; // 密码
        $model->email = Arr::get($input, 'email', $model->email); // 邮箱
        $model->phone = Arr::get($input, 'phone', $model->phone); // 手机
        $model->mfa_secure_key = $mfa_secure_key ? Crypt::encryptString($mfa_secure_key) : $model->mfa_secure_key; // MFA 秘钥
        $model->mfa_status = $mfa_status === null ? $model->mfa_status : $mfa_status; // 启用 TOTP MFA (两步验证 2FA)：0 不启用，1 启用

        // 验证是否可以启用MFA校验
        if ($model->mfa_status == UserMfaStatus::ENABLED->value && empty($model->mfa_secure_key)) {
            throw new CustomizeException(Code::E100068);
        }
        $old_avatar = $model->avatar;
        $model->avatar = Arr::get($input, 'avatar', $model->avatar); // 头像
        $model->status = Arr::get($input, 'status', $model->status); // 是否禁用
        $model->remark = Arr::get($input, 'remark', $model->remark); // 备注
        $model->updated_at = date('Y-m-d H:i:s'); // 创建时间


        // 更新
        $res = $model->save();
        if ($res) {
            if ($this->checkUserInfoCacheExists($id)) {
                $user = $model->toArray();
                $this->setUserInfoCache($id, $user);
            }

            // 文件处理
            if ($model->avatar && $model->avatar != $old_avatar) {
                if ($old_avatar) {
                    // 设置旧文件过期删除
                    (new FileService)->updateStatus($old_avatar, FileStatus::TOBEDELETED);
                }
                // 启用新文件
                (new FileService)->updateStatus($model->avatar, FileStatus::USING);
            }
        }
        return $res;
    }

    /**
     * 修改密码
     * @param $id
     * @param $oldPassword
     * @param $newPassword
     * @return bool
     * @throws CustomizeException
     */
    public function updatePassword($id, $oldPassword, $newPassword): bool
    {
        $user = User::find($id);
        if (!$user) {
            throw new CustomizeException(Code::E100021);
        }

        if (!Hash::check($oldPassword . substr(md5($user->name), 10, 10), $user->password)) {
            throw new CustomizeException(Code::E100005);
        }

        return $user->update([
            'password' => Hash::make($newPassword . substr(md5($user->name), 10, 10)),
        ]);
    }

    /**
     * 清除用户缓存信息
     * @param int $uid 用户id
     * @return bool
     * @throws RedisException
     */
    public function clearUserInfo(int $uid): bool
    {
        // 清除用户token
        $this->delTokenCache($uid);

        // 清除用户信息缓存
        $this->delUserInfoCache($uid);

        // 清除用户角色缓存
        $this->delUserRoleCache($uid);

        // 清除用户登录校验MFA设备标识

        return true;
    }

    /**
     * user.index
     * @param array $input
     * @return array
     */
    public function list(array $input): array
    {
        // 查询条件
        $email = Arr::get($input, 'email'); // email
        $name = Arr::get($input, 'name'); // name
        $role = Arr::get($input, 'role'); // role
        $status = Arr::get($input, 'status'); // status

        // 分页, 排序
        $orderByField = Arr::get($input, 'field', 'id'); // 排序字段
        $orderByType = OrderBy::getLabel(Arr::get($input, 'order')); // 排序方式
        $page = Arr::get($input, 'page', 1); // 页码
        $pageSize = Arr::get($input, 'pageSize', 10); // 每页条数

        // 查询
        $query = User::from((new User)->getTable(), 'u')->when($email, function (Builder $query) use ($email) { // email
            return $query->where('email', $email);
        })->when($name, function (Builder $query) use ($name) { // name
            return $query->where('name', $name);
        })->when($status !== null, function (Builder $query) use ($status) { // status
            return $query->where('status', $status);
        })->when($role !== null, function (Builder $query) use ($role) { // role
            return $query->join((new UserRoleAccess)->tableName('r'), 'u.id', '=', 'r.user_id')
                ->where('r.role_id', $role);
        });

        // 总数
        $total = $query->count();
        $items = [];
        if ($total) {
            // 排序,分页
            $items = $query->select('u.*')->orderBy($orderByField, $orderByType)
                ->offset($pageSize * ($page - 1))->limit($pageSize)->get();
        }

        return ['total' => $total, 'items' => $items];
    }

    /**
     * 绑定google安全秘钥
     * @param $id
     * @param $secureKey
     * @return bool
     * @throws RedisException
     */
    public function buildMfaSecureKey($id, $secureKey): bool
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        $update = [
            'mfa_secure_key' => $secureKey,
            'mfa_status' => UserMfaStatus::ENABLED->value, // 每次重新绑定后默认启用
        ];

        $res = $user->update($update);

        // 更新缓存
        $this->cacheUserInfo($user);

        // 绑定成功删除缓存
        $this->delMfaSecret($user->id);

        return $res;
    }


    /**
     * 设置两步验证码
     * @param int $uid
     * @param int $scenarios CheckMfaScenarios::xxx校验场景
     * @param int $expire 过期时间
     * @return array
     * @throws CustomizeException
     * @throws RedisException
     */
    public function setTwoStepCode(int $uid, int $scenarios, int $expire = 1800): array
    {
        // 拼接key
        $key = self::$APP_NAME . RedisKeys::USER_TWO_STEP . $uid . RedisKeys::DELIMIT . $scenarios;

        // 生成随机码
        $code = rand(60466176, 2176782335); // 60466176 - 2176782335 => 100000 - zzzzzz

        // 十进制转36进制
        // $suffix = base_convert($code, 10, 36); // 100000-zzzzzz

        // 值
        $twoStepCode = base64_encode(json_encode(['code' => $code, 'stime' => time(), 'expire' => $expire, 'id' => $uid]));

        // 存入缓存
        if (!Service::set($key, $twoStepCode, $expire)) {
            throw new CustomizeException(Code::F5006, ['flag' => 'set']);
        }

        return ['key' => $scenarios, 'expire' => time() + $expire, 'value' => $twoStepCode];
    }

    /**
     * 校验两步验证码
     * @param int $uid
     * @param int $scenarios CheckMfaScenarios::xxx校验场景
     * @return string
     * @throws RedisException
     */
    public function checkTwoStepCode(int $uid, int $scenarios): string
    {
        // 拼接key
        $key = self::$APP_NAME . RedisKeys::USER_TWO_STEP . $uid . RedisKeys::DELIMIT . $scenarios;
        if (Service::redis()->exists($key)) {
            return Service::get($key);
        }

        return false;
    }

    /**
     * 设置用户登录校验MFA设备标识
     * @param $uid
     * @return bool
     * @throws RedisException
     */
    public function setLoginCheckMfaFlag($uid): bool
    {
        return Service::set(self::$APP_NAME . RedisKeys::LOGIN_CHECK_MFA_FLAG . $uid, time(), 24 * 60 * 60);
    }

    /**
     * 获取用户登录检验MFA设备标识
     * @param int $uid
     * @return mixed
     * @throws RedisException
     */
    public function getLoginCheckMfaFlag(int $uid): mixed
    {
        return Service::get(self::$APP_NAME . RedisKeys::LOGIN_CHECK_MFA_FLAG . $uid);
    }

    /**
     * 获取秘钥
     * @param int $uid
     * @return mixed
     * @throws RedisException
     */
    public function getCacheMfaSecret(int $uid)
    {
        return Service::get(self::$APP_NAME . RedisKeys::MFA_SECRET . $uid);
    }

    /**
     * 设置秘钥缓存
     * @param int $uid
     * @param string $secret
     * @return bool
     * @throws RedisException
     */
    public function setCacheMfaSecret(int $uid, string $secret): bool
    {
        return Service::set(self::$APP_NAME . RedisKeys::MFA_SECRET . $uid, $secret, 300);
    }

    /**
     * 获取秘钥缓存过期时间
     * @param int $uid
     * @return bool|int
     * @throws RedisException
     */
    public function getMfaSecretTtl(int $uid): bool|int
    {
        return Service::redis()->ttl(self::$APP_NAME . RedisKeys::MFA_SECRET . $uid);
    }

    /**
     * 删除秘钥缓存
     * @param int $uid
     * @return false|int
     * @throws RedisException
     */
    public function delMfaSecret(int $uid): bool|int
    {
        return Service::redis()->del(self::$APP_NAME . RedisKeys::MFA_SECRET . $uid);
    }

    /**
     * user.roleList
     * @param int $adminId
     * @param int $uid
     * @return array
     * @throws RedisException
     */
    public function userRoleList(int $adminId, int $uid): array
    {
        $list = [];
        DB::table((new UserRoleAccess)->getTable(), 'a')
            ->join((new Role)->tableName('r'), 'a.role_id', 'r.id')
            ->where('a.user_id', $uid)
            ->select(['a.*', 'r.title'])
            ->orderBy('role_id')
            ->lazy()->each(function ($role) use ($adminId, &$list) {
                $role->isUpdate = $this->checkUserHasChildRole($adminId, $role->role_id);
                $list[] = $role;
            });
        return $list;
    }

    /**
     * user.editRole
     * @param int $adminId
     * @param int $uid
     * @param array $input
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function userEditRole(int $adminId, int $uid, array $input): bool
    {
        $roleIds = Arr::get($input, 'roles');
        if (!$roleIds || !is_array($roleIds)) {
            throw new CustomizeException(Code::E100049);
        }
        // 过滤出正常的角色
        $idArr = Role::where([['status', RoleStatus::ENABLED], ['is_delete', Delete::NO]])->whereIn('id', $roleIds)->pluck('id')->toArray();
        if (!$idArr) {
            throw new CustomizeException(Code::E100044);
        }

        $idArr2 = UserRoleAccess::where([['user_id', $uid]])->pluck('role_id')->toArray();

        // 计算要删除的数据
        $delArr = array_diff($idArr2, $idArr);
        // 验证是否有权限删除该记录
        if ($delArr) {
            foreach ($delArr as $id) {
                if (!$this->checkUserHasChildRole($adminId, $id)) {
                    $title = Role::where('id', $id)->value('title');
                    throw new CustomizeException(Code::E100050, compact('title'));
                }
            }

        }

        // 计算要新增的数据
        $insertArr = array_diff($idArr, $idArr2);
        // 验证是否有权限添加该记录
        if ($insertArr) {
            foreach ($insertArr as $id) {
                if (!$this->checkUserHasChildRole($adminId, $id)) {
                    $title = Role::where('id', $id)->value('title');
                    throw new CustomizeException(Code::E100045, compact('title'));
                }
            }
        }

        // 验证完毕 数据入库
        DB::beginTransaction();
        try {
            // 删除记录
            if ($delArr) {
                $res = DB::table((new UserRoleAccess)->tableName())->where('user_id', $uid)->whereIn('role_id', $delArr)->delete();
                if (!$res) {
                    throw new CustomizeException(Code::E100051);
                }
            }

            // 新增数据
            if ($insertArr) {
                $insertData = [];
                foreach ($insertArr as $id) {
                    $insertData[] = ['user_id' => $uid, 'role_id' => $id, 'created_at' => date('Y-m-d H:i:s')];
                }
                $res = DB::table((new UserRoleAccess)->tableName())->insert($insertData);
                if (!$res) {
                    throw new CustomizeException(Code::E100052);
                }
            }

            // 刷新角色缓存(删除后获取角色缓存数据时自动刷新)
            $this->delUserRoleCache($uid);

            // 提交事务
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Logger::error(LogChannel::DEV, __METHOD__, compact('uid', 'delArr', 'insertArr', $e));
            throw new CustomizeException($e->getCode(), $e->getMessage());
        }

        return true;
    }

    /**
     * user.addRole
     * @param int $adminId
     * @param int $uid
     * @param array $input
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function userAddRole(int $adminId, int $uid, array $input): bool
    {
        $roleId = Arr::get($input, 'roleId', 0);
        $title = Role::where([['id', $roleId], ['status', RoleStatus::ENABLED], ['is_delete', Delete::NO]])->value('title');
        if ($title == null) {
            throw new CustomizeException(Code::E100044);
        }

        if (UserRoleAccess::where([['role_id', $roleId], ['user_id', $uid]])->exists()) {
            return true;
        }

        if (!$this->checkUserHasChildRole($adminId, $roleId)) {
            throw new CustomizeException(Code::E100045, compact('title'));
        }

        $model = new UserRoleAccess;
        $model->user_id = $uid;
        $model->role_id = $roleId;
        $model->created_at = date('Y-m-d H:i:s'); // 创建时间

        $res = $model->save();
        if ($res) {
            // 刷新角色缓存(删除后获取角色缓存数据时自动刷新)
            $this->delUserRoleCache($uid);
        }
        return $res;
    }

    /**
     * user.delRole
     * @param int $adminId
     * @param int $uid
     * @param array $input
     * @return bool
     * @throws CustomizeException|RedisException
     */
    public function userDelRole(int $adminId, int $uid, array $input): bool
    {
        $roleId = Arr::get($input, 'user_roles_id', 0);

        // 查找用户与角色关联记录
        $model = UserRoleAccess::find($roleId);
        if (!$model) {
            return true;
        }

        // 验证是否有权限删除该记录
        if (!$this->checkUserHasChildRole($adminId, $model->role_id)) {
            throw new CustomizeException(Code::E100046);
        }

        // 删除用户和角色关系
        $res = $model->delete();
        if ($res) {
            // 刷新角色缓存(删除后获取角色缓存数据时自动刷新)
            $this->delUserRoleCache($uid);
        }
        return $res;
    }

    /**
     * 用户角色id
     * @param int $id
     * @return array
     */
    public function userRole(int $id): array
    {
        // 获取账号角色
        return UserRoleAccess::where('user_id', $id)->pluck('role_id')->toArray();
    }

    /**
     * 账号管理 编辑|新增角色下拉列表
     * @param int $uid 管理员ID
     * @return array
     * @throws RedisException
     */
    public function userRoleTreeList(int $uid): array
    {
        // 从缓存中获取角色
        $data = RedisService::getTable(RedisKeys::ROLE_TREE, true);
        if ($data) {
            $data = json_decode($data, true);

            // 获取用户角色
            $roles = $this->getUserRole($uid);
            $isSuperRole = false;
            if (in_array(Role::getSuperRole(), $roles)) {
                $isSuperRole = true;
            }

            // 过滤禁用的角色
            self::arrayWalkRecursive($data, function (&$arr, $key, $item) use ($isSuperRole, &$roles) {
                if ($item['status'] != RoleStatus::ENABLED->value || $item['is_delete'] != Delete::NO->value) {
                    unset($arr[$key]);
                } else {
                    // 将子级角色添加到角色列表中
                    if (isset($item['children']) && in_array($item['pid'], $roles) && !in_array($item['id'], $roles)) {
                        $roles[] = $item['id'];
                    }

                    $disabled = $isSuperRole || in_array($item['pid'], $roles);

                    $arr[$key]['name'] = $item['title']; // 前端框架title被包装了无法获取额外加个name字段
                    $arr[$key]['disabled'] = !$disabled; // 禁止编辑
                    $arr[$key]['disableCheckbox'] = !$disabled; // 上级有得角色才可以编辑
                    $arr[$key]['selectable'] = $disabled; // 上级有得权限才可以编辑
                }
            }, 'children');

            return $data;
        }
        return [];
    }


    /**
     * 获取用户角色 更新
     * @param int $uid
     * @param bool $renew
     * @return array
     * @throws RedisException
     */
    public function getUserRole(int $uid, bool $renew = false): array
    {
        // 从缓存中获取用户角色
        $roles = $renew ? [] : $this->getUserRoleCache($uid);

        // 刷新缓存
        if (empty($roles) && ($renew || !$this->checkUserRoleCacheExists($uid))) {
            // 查询数据表, 获取 role_id 集合
            $roles = DB::table((new UserRoleAccess)->tableName('u'))->join((new Role())->tableName('r'), 'u.role_id', '=', 'r.id')
                ->where('u.user_id', $uid)
                ->where('r.status', RoleStatus::ENABLED)
                ->where('r.is_delete', Delete::NO)
                ->orderBy('r.id')
                ->pluck('r.id')
                ->toArray();

            // 删除缓存
            if ($renew) $this->delUserRoleCache($uid);

            // 写入缓存
            $this->setUserRoleCache($uid, $roles);
        }

        return $roles;
    }


    /**
     * 获取用户MFA信息（适用当前登录的用户）
     * @param int $uid
     * @param int $scenarios CheckMfaScenarios ::xxx校验场景
     * @return array
     * @throws RedisException
     */
    public function getUserMfaInfo(int $uid, int $scenarios): array
    {
        // 获取用户缓存信息
        $user = $this->getUserInfo($uid);

        $userMfaInfo = [];
        $userMfaInfo['exist_mfa'] = $user['mfa_secure_key'] != ''; // 是否已绑定
        $sign = $this->generateSign(['id' => $uid, 'name' => $user['name']]);
        $userMfaInfo['build_mfa_url'] = "/mfa/secret/$sign"; // 绑定地址
        $userMfaInfo['scenarios'] = $scenarios; // 校验场景
        $userMfaInfo['mfa_status'] = (int)$user['mfa_status']; // 用户启用状态
        $userMfaInfo['mfa_check'] = $userMfaInfo['mfa_status']; // 校验状态
        // 是否强制启用登录校验
        $MFACheckEnable = ConfigService::getCache(ConfigUuid::MFA_CHECK_ENABLE);
        if ($MFACheckEnable) {
            $userMfaInfo['mfa_check'] = UserMfaStatus::ENABLED;
        }

        return $userMfaInfo;
    }
}
