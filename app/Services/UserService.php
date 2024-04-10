<?php

namespace App\Services;

use App\Enum\Code;
use App\Enum\ConfigUuid;
use App\Enum\LogChannel;
use App\Enum\OrderBy;
use App\Enum\UserStatus;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Models\User;
use App\Models\UserRolesAccess;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use stdClass;
use Throwable;

class UserService extends Service
{
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
            RedisService::setToken($user->id, $token); //token 保存redis服务器
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
     * @throws CustomizeException
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
            if (true !== ConfigService::getCache(ConfigUuid::CHECK_CHANGE_IP) && $jwt->ip != $ip) {
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
        $temp_token = RedisService::getToken($uid);
        if ($temp_token != $token) {
            throw new CustomizeException(Code::E100018);
        }

        RedisService::renewToken($uid); //更新token过期时间

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
     * 获取用户信息
     * @param int $uid
     * @param array $fields
     * @return array
     */
    public function getUserInfo(int $uid, array $fields = []): array
    {
        $user = RedisService::getUserInfo($uid, $fields);
        if (empty($user)) {
            $user = User::find($uid)->toArray();
            RedisService::setUserInfo($uid, $user);
        }
        return $user;
    }

    /**
     * 登录验证
     * @param Request $request
     * @param string $name
     * @param string $password
     * @return User
     * @throws CustomizeException
     */
    public function userCheck(Request $request, string $name, string $password): User
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
     */
    public function cacheUserInfo(User $user): void
    {
        // 缓存用户信息
        RedisService::setUserInfo($user->id, $user->toArray());

        // 更新用户角色缓存
        (new AuthorizeService)->getUserRoles($user->id, true);
    }

    /**
     * 验证密码
     * @param int $id
     * @param string $password
     * @return bool
     * @throws CustomizeException
     */
    public function CheckPassword(int $id, string $password): bool
    {
        $user = User::where('id', $id)->first();
        if (!$user) {
            throw new CustomizeException(Code::E100015);
        }

        // 验证密码
        if (Hash::check($password . substr(md5($user->name), 10, 10), $user->password)) {
            return true;
        }

        return false;
    }

    /**
     * 添加账号
     * @param Request $request
     * @param array $input
     * @return array
     * @throws CustomizeException
     */
    public function addAccount(Request $request, array $input): array
    {
        $name = Arr::get($input, 'name'); // 账号
        $real_name = Arr::get($input, 'real_name', ''); // 真实姓名
        $password = Arr::get($input, 'password', Str::random(32)); // 密码
        $email = Arr::get($input, 'email', ''); // 邮箱
        $phone = Arr::get($input, 'phone', ''); // 邮箱
        $secure_key = Arr::get($input, 'secure_key'); // 安全码秘钥
        $status = Arr::get($input, 'status', UserStatus::ENABLED); // 状态
        $avatar = Arr::get($input, 'avatar', ''); // 状态
        $remark = Arr::get($input, 'remark', ''); // 状态

        if (User::where('name', $name)->exists()) {
            throw new CustomizeException(Code::E100047, ['name' => $name]);
        }

        // 添加数据
        $model = new User();
        $model->name = $name;
        $model->real_name = $real_name;
        $model->password = Hash::make($password . substr(md5($name), 10, 10));
        $model->email = $email;
        $model->phone = $phone;
        $model->secure_key = $secure_key ? Crypt::encryptString($secure_key) : '';
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

        return $model->toArray();
    }

    /**
     * 编辑账号
     * @param Request $request
     * @param int $id
     * @param array $input
     * @return bool
     * @throws CustomizeException
     */
    public function editAccount(Request $request, int $id, array $input): bool
    {
        $password = Arr::get($input, 'password'); // 密码
        $secure_key = Arr::get($input, 'secure_key'); // 密码

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
        $model->secure_key = $secure_key ? Crypt::encryptString($secure_key) : $model->secure_key; // 安全码秘钥
        $model->status = Arr::get($input, 'status', $model->status); // 是否禁用
        $model->avatar = Arr::get($input, 'avatar', $model->avatar); // 头像
        $model->remark = Arr::get($input, 'remark', $model->remark); // 备注
        $model->updated_at = date('Y-m-d H:i:s'); // 创建时间

        // 更新
        $res = $model->save();
        if ($res) {
            if (RedisService::checkUserInfoExists($id)) {
                $user = $model->toArray();
                RedisService::setUserInfo($id, $user);
            }
        }
        return $res;
    }

    /**
     * 修改密码
     * @param Request $request
     * @param $id
     * @param $oldPassword
     * @param $newPassword
     * @return bool
     * @throws CustomizeException
     */
    public function updatePassword(Request $request, $id, $oldPassword, $newPassword): bool
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
     * 清楚用户缓存信息
     * @param int $uid 用户id
     * @return bool
     */
    public function clearUserInfo(int $uid): bool
    {
        // 清楚用户token
        RedisService::delToken($uid);

        // 清楚用户信息缓存
        RedisService::delUserInfo($uid);

        // 清楚用户角色缓存
        RedisService::delUserRoles($uid);
        return true;
    }

    /**
     * user.index
     * @param Request $request
     * @param array $input
     * @return array
     */
    public function list(Request $request, array $input): array
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
        $query = User::when($email, function ($query) use ($email) { // email
            return $query->where('email', $email);
        })->when($name, function ($query) use ($name) { // name
            return $query->where('name', $name);
        })->when($status !== null, function ($query) use ($status) { // status
            return $query->where('status', $status);
        })->when($role !== null, function ($query) use ($role) { // role
            return $query->join((new UserRolesAccess)->tableName('role'), 'users.id', '=', 'role.user_id')
                ->where('role.role_id', $role);
        });

        // 总数
        $total = $query->count();
        $items = [];
        if ($total) {
            // 排序,分页
            $items = $query->select([
                'users.id', 'users.name', 'users.real_name', 'users.email', 'users.phone', 'users.secure_key', 'users.status'
                , 'users.avatar', 'users.remark', 'users.last_login_time', 'users.last_login_ip'
                , 'users.last_login_ipaddr', 'users.created_at', 'users.updated_at'
            ])->orderBy($orderByField, $orderByType)
                ->offset($pageSize * ($page - 1))->limit($pageSize)->get();
        }

        return ['total' => $total, 'items' => $items];
    }

    /**
     * 绑定google安全秘钥
     * @param Request $request
     * @param $id
     * @param $secureKey
     * @return bool
     */
    public function buildSecureKey(Request $request, $id, $secureKey): bool
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        $ip = $request->getClientIp();
        $update = [
            'secure_key' => $secureKey,
            'last_login_ip' => $ip,
            'last_login_ipaddr' => IpService::getIpAddr($ip)
        ];

        return $user->update($update);
    }
}
