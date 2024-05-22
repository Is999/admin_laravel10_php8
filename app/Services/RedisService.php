<?php

namespace App\Services;

use App\Enum\LogChannel;
use App\Enum\RedisKeys;
use App\Enum\RedisType;
use App\Logging\Logger;
use Illuminate\Support\Facades\App;
use RedisException;
use Throwable;

class RedisService extends Service
{
    const TABLE_CONFIG_FILE = 'tablecache'; // config目录下的配置文件


    /**
     * 存储token
     * @param int $uid
     * @param string $value
     * @return bool
     * @throws RedisException
     */
    public static function setToken(int $uid, string $value): bool
    {
        return self::set(RedisKeys::ADMIN_TOKEN . $uid, $value, 3600);
    }

    /**
     * 获取token
     * @param int $uid
     * @return mixed false|mixed|string
     * @throws RedisException
     */
    public static function getToken(int $uid): mixed
    {
        return self::redis()->get(RedisKeys::ADMIN_TOKEN . $uid);
    }

    /**
     * 更新Token 过期时间
     * @param int $uid
     * @param int $ttl
     * @return bool
     * @throws RedisException
     */
    public static function renewToken(int $uid, int $ttl = 3600): bool
    {
        return self::redis()->expire(RedisKeys::ADMIN_TOKEN . $uid, $ttl);
    }

    /**
     * 删除token
     * @param int $uid
     * @return int
     * @throws RedisException
     */
    public static function delToken(int $uid): int
    {
        return self::redis()->del(RedisKeys::ADMIN_TOKEN . $uid);
    }

    /**
     * 存储用户信息
     * @param int $uid
     * @param array $userInfo
     * @return bool
     * @throws RedisException
     */
    public static function setUserInfo(int $uid, array $userInfo): bool
    {
        $res = self::redis()->hMSet(RedisKeys::ADMIN_USERINFO . $uid, $userInfo);
        if ($res) {
            self::redis()->expire(RedisKeys::ADMIN_USERINFO . $uid, 3600 * 8);
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
    public static function getUserInfo(int $uid, array $fields = []): array
    {
        if (empty($fields)) {
            return self::redis()->hGetAll(RedisKeys::ADMIN_USERINFO . $uid);
        }
        return self::redis()->hMGet(RedisKeys::ADMIN_USERINFO . $uid, $fields);
    }

    /**
     * 是否存在用户信息
     * @param int $uid
     * @return bool|int
     * @throws RedisException
     */
    public static function checkUserInfoExists(int $uid): bool|int
    {
        return self::redis()->exists(RedisKeys::ADMIN_USERINFO . $uid);
    }


    /**
     * 删除用户信息
     * @param int $uid
     * @return int
     * @throws RedisException
     */
    public static function delUserInfo(int $uid): int
    {
        return self::redis()->del(RedisKeys::ADMIN_USERINFO . $uid);
    }

    /**
     * 存储用户角色信息
     * @param int $uid
     * @param array $roles
     * @return bool
     * @throws RedisException
     */
    public static function setUserRoles(int $uid, array $roles): bool
    {
        $key = RedisKeys::ADMIN_USER_ROLES . $uid;
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
    public static function getUserRoles(int $uid): array
    {
        return self::redis()->sMembers(RedisKeys::ADMIN_USER_ROLES . $uid);
    }

    /**
     * 是否存在用户角色信息
     * @param int $uid
     * @return bool|int
     * @throws RedisException
     */
    public static function checkUserRolesExists(int $uid): bool|int
    {
        return self::redis()->exists(RedisKeys::ADMIN_USER_ROLES . $uid);
    }

    /**
     * 删除用户角色信息
     * @param int $uid
     * @return int
     * @throws RedisException
     */
    public static function delUserRoles(int $uid): int
    {
        return self::redis()->del(RedisKeys::ADMIN_USER_ROLES . $uid);
    }

    /**
     * 存储角色权限信息
     * @param int $id
     * @param array $permissions
     * @return bool
     * @throws RedisException
     */
    public static function setRolePermissions(int $id, array $permissions): bool
    {
        return self::loadSet(RedisKeys::ROLES_PERMISSIONS . $id, $permissions);
    }

    /**
     * 获取角色权限信息
     * @param int $id
     * @return array
     * @throws RedisException
     */
    public static function getRolePermissions(int $id): array
    {
        return self::sMembersTable(RedisKeys::ROLES_PERMISSIONS . $id, true);
    }

    /**
     * 删除角色信息
     * @param int $id
     * @return int
     * @throws RedisException
     */
    public static function delRolePermissions(int $id): int
    {
        return self::redis()->del(RedisKeys::ROLES_PERMISSIONS . $id);
    }

    /**
     * 存储角色status信息
     * @param array $roles
     * @return bool
     * @throws RedisException
     */
    public static function setRolesStatus(array $roles): bool
    {
        return self::loadHash(RedisKeys::ROLES_STATUS, $roles);
    }

    /**
     * 获取权限module信息
     * @param string $roleId
     * @return mixed
     * @throws RedisException
     */
    public static function getRoleStatus(string $roleId): mixed
    {
        return self::hGetTable(RedisKeys::ROLES_STATUS, $roleId);
    }

    /**
     * 删除权限module信息
     * @param array $roleIds
     * @return int
     * @throws RedisException
     */
    public static function delRolesStatus(array $roleIds): int
    {
        return self::redis()->hDel(RedisKeys::ROLES_STATUS, ...$roleIds);
    }

    /**
     * 存储权限module信息
     * @param array $permissions
     * @return bool
     * @throws RedisException
     */
    public static function setPermissionsModule(array $permissions): bool
    {
        return self::loadHash(RedisKeys::PERMISSIONS_MODULE, $permissions);
    }

    /**
     * 获取权限module信息
     * @param array $permissionIds
     * @return array
     * @throws RedisException
     */
    public static function getPermissionsModule(array $permissionIds): array
    {
        return self::hMGetTable(RedisKeys::PERMISSIONS_MODULE, $permissionIds, true);
    }

    /**
     * 删除权限module信息
     * @param int $permissionIds
     * @return int
     * @throws RedisException
     */
    public static function delPermissionsModule(int $permissionIds): int
    {
        return self::redis()->hDel(RedisKeys::PERMISSIONS_MODULE, $permissionIds);
    }

    /**
     * 存储权限uuid信息
     * @param array $permissions
     * @return bool
     * @throws RedisException
     */
    public static function setPermissionsUuid(array $permissions): bool
    {
        return self::loadHash(RedisKeys::PERMISSIONS_UUID, $permissions);
    }

    /**
     * 获取权限uuid信息
     * @param array $permissionIds
     * @return array
     * @throws RedisException
     */
    public static function getPermissionsUuid(array $permissionIds): array
    {
        return self::hMGetTable(RedisKeys::PERMISSIONS_UUID, $permissionIds, true);
    }

    /**
     * 删除权限uuid信息
     * @param int $permissionIds
     * @return int
     * @throws RedisException
     */
    public static function delPermissionsUuid(int $permissionIds): int
    {
        return self::redis()->hDel(RedisKeys::PERMISSIONS_UUID, $permissionIds);
    }

    /**
     * 缓存Hash类型数据
     * @param string $key
     * @param array $data
     * @param int $expire
     * @return bool
     * @throws RedisException
     */
    private static function loadHash(string $key, array $data, int $expire = 0): bool
    {
        // 将对应的键存入队列中
        $res = self::redis()->hMSet($key, $data);

        // 设置过期时间
        if ($res && $expire > 0) {
            self::redis()->expire($key, $expire + rand(0, 3600 * 24));
        }

        return $res;
    }

    /**
     * 缓存list类型数据
     * @param string $key
     * @param array $data
     * @param int $expire
     * @return bool|int
     * @throws RedisException
     */
    private static function loadList(string $key, array $data, int $expire = 0): bool|int
    {
        // 将对应的键存入队列中
        $res = self::redis()->rPush($key, ...$data);

        // 设置过期时间
        if ($res && $expire > 0) {
            self::redis()->expire($key, $expire + rand(0, 3600 * 24));
        }

        return $res;
    }

    /**
     * 缓存set集合类型数据
     * @param string $key
     * @param array $data
     * @param int $expire
     * @return bool|int
     * @throws RedisException
     */
    private static function loadSet(string $key, array $data, int $expire = 0): bool|int
    {
        // 将对应的键存入队列中 分隔符
        $res = self::redis()->sAddArray($key, $data);

        // 设置过期时间
        if ($res && $expire > 0) {
            self::redis()->expire($key, $expire + rand(0, 3600 * 24));
        }

        return $res;
    }

    /**
     * 缓存有序集合类型数据
     * @param string $key
     * @param array $data
     * @param int $expire
     * @return bool|int
     * @throws RedisException
     */
    private static function loadSortedSet(string $key, array $data, int $expire = 0): bool|int
    {
        // 将对应的键存入队列中 分隔符
        $res = self::redis()->zAdd($key, ...$data);

        // 设置过期时间
        if ($res && $expire > 0) {
            self::redis()->expire($key, $expire);
        }

        return $res;
    }

    /**
     * 缓存string 类型数据
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return bool
     * @throws RedisException
     */
    private static function loadString(string $key, mixed $value, int $expire = 0): bool
    {
        // 设置随机过期时间或永不过期
        $expire = $expire ? $expire + rand(0, 3600 * 24) : 0;

        // 将查询数据写入redis
        return self::set($key, $value, $expire);
    }

    /**
     * 删除所有的缓存
     * @param string $key
     * @throws RedisException
     */
    public static function flushTableAll(string $key = '*'): void
    {
        if (str_ends_with($key, RedisKeys::DELIMIT)) {
            $key .= '*';
        }
        self::delKeysScan($key);
    }

    /**
     * 初始化缓存数据
     * @param string $key
     * @param array $secondIndex
     * @return bool|array
     * @throws RedisException
     * @example
     * <pre>
     * RedisService::initTable() // 刷新配置文件所有的key
     * RedisService::initTable('roles:') // set集合 刷新所有角色的权限
     * RedisService::initTable('roles:2') // set集合 刷新角色id为2的权限
     * RedisService::initTable('permissions') // hash 刷新所有权限module
     * RedisService::initTable('permissions', [1,2,3]) // hash 刷新权限id为1,2,3的module, 需要call方法支持
     * </pre>
     */
    public static function initTable(string $key = '*', array $secondIndex = []): bool|array
    {
        Logger::info(LogChannel::CACHE, 'initTable: ~~~准备初始化[{{key}}]缓存~~~', compact('key', 'secondIndex'));
        // 加载配置
        $config = config(self::TABLE_CONFIG_FILE);
        if (!$config) {
            return false;
        }

        // 判断key
        if ($key !== '*') {
            $index = explode(':', $key)[0];
            if (!isset($config[$index])) {
                Logger::notice(LogChannel::CACHE, 'initTable: {{0}} config/{{1}}.php 配置不存在 ' . $index, [$key, self::TABLE_CONFIG_FILE]);
                return false;
            }
            $config = [$index => $config[$index]];
        }

        // 携带参数[params]属于key部分缓存内容更新,不适用删除key
        // 如: hash 需要要call方法支持传参 更新key中某一个或多个元素
        if (str_ends_with($key, RedisKeys::DELIMIT) || !$secondIndex) {
            Logger::info(LogChannel::CACHE, 'initTable: 准备删除[{{key}}]旧缓存~~~', compact('key', 'secondIndex'));

            // 删除缓存
            if ($key === '*') {
                foreach ($config as $k => $v) {
                    if ($v['key'] && explode(':', $v['key'])[0] == $k) {
                        self::flushTableAll($v['key']);
                    }
                }
            } else {
                self::flushTableAll($key);
            }

            Logger::info(LogChannel::CACHE, 'initTable: ~~~旧缓存[{{key}}]删除完毕', compact('key', 'secondIndex'));
        }

        // call_user_func 参数接收两个值 key, index
        $params = [
            'key' => array_slice(array_filter(explode(':', $key), function ($v) {
                return $v !== '';
            }), 1),  // 去掉key前缀 其它数据作为参数传递到方法中
            'index' => $secondIndex
        ];
        Logger::info(LogChannel::CACHE, 'initTable: call 参数', compact('key', 'params'));

        $list = [];

        // 获取数据并存储到缓存
        foreach ($config as $k => $v) {
            // 记录日志
            Logger::info(LogChannel::CACHE, 'initTable: {{0}} 开始缓存数据~~~', [$k, 'config' => $v]);

            if (!self::checkTableConfig($k, $v)) {
                Logger::notice(LogChannel::CACHE, 'initTable: {{0}} checkTableConfig 配置错误', [$k, 'config' => $v]);
                continue;
            }

            try {
                // 调取方法获取数据
                $data = App::call($v['class'], $params, $v['method']);
                //$data = call_user_func(array($v['class'], $v['method']), ...$params);
            } catch (Throwable $e) {
                Logger::error(LogChannel::CACHE, 'initTable: {{0}} call {{1}}::{{2}} 失败', [$k, $v['class'], $v['method'], 'config' => $v, 'params' => $params], $e);
                break;
            }

            if ($data === false || !is_array($data)) {
                Logger::warning(LogChannel::CACHE, 'initTable: {{0}} 获取数据错误', [$k, 'config' => $v, 'data' => $data]);
                continue;
            }
            $expire = $v['expire'] ?? 0; // 过期时间

            $temp = [];
            try {
                // 数据存入缓存
                switch ($v['type']) {
                    case RedisType::String:
                        if (str_ends_with($v['key'], RedisKeys::DELIMIT)) {
                            foreach ($data as $sk => $sv) {
                                Logger::info(LogChannel::CACHE, 'initTable: STRING {{0}} 开始--', [$v['key'] . $sk, 'data' => $sv]);

                                $res = self::loadString($v['key'] . $sk, $sv, $expire);

                                $temp[$v['key'] . $sk] = $res;
                            }
                        } else {
                            Logger::info(LogChannel::CACHE, 'initTable: STRING {{0}} 开始--', [$v['key'], 'data' => $data]);

                            $res = self::loadString($v['key'], json_encode($data), $expire);

                            $temp[$v['key']] = $res;
                        }
                        break;
                    case RedisType::Hash:
                        if (str_ends_with($v['key'], RedisKeys::DELIMIT)) {
                            foreach ($data as $sk => $sv) {
                                Logger::info(LogChannel::CACHE, 'initTable: HASH {{0}} 开始--', [$v['key'] . $sk, 'data' => $sv]);

                                $res = self::loadHash($v['key'] . $sk, $sv, $expire);

                                $temp[$v['key'] . $sk] = $res;
                            }
                        } else {
                            Logger::info(LogChannel::CACHE, 'initTable: HASH {{0}} 开始--', [$v['key'], 'data' => $data]);

                            $res = self::loadHash($v['key'], $data, $expire);

                            $temp[$v['key']] = $res;
                        }
                        break;
                    case RedisType::List:
                        if (str_ends_with($v['key'], RedisKeys::DELIMIT)) {
                            foreach ($data as $sk => $sv) {
                                Logger::info(LogChannel::CACHE, 'initTable: LIST {{0}} 开始--', [$v['key'] . $sk, 'data' => $sv]);

                                $res = self::loadList($v['key'] . $sk, $sv, $expire);

                                $temp[$v['key'] . $sk] = $res;
                            }
                        } else {
                            Logger::info(LogChannel::CACHE, 'initTable: LIST {{0}} 开始--', [$v['key'], 'data' => $data]);

                            $res = self::loadList($v['key'], $data, $expire);

                            $temp[$v['key']] = $res;
                        }
                        break;
                    case RedisType::Set:
                        if (str_ends_with($v['key'], RedisKeys::DELIMIT)) {
                            foreach ($data as $sk => $sv) {
                                Logger::info(LogChannel::CACHE, 'initTable: SET {{0}} 开始--', [$v['key'] . $sk, 'data' => $sv]);

                                $res = self::loadSet($v['key'] . $sk, $sv, $expire);

                                $temp[$v['key'] . $sk] = $res;
                            }
                        } else {
                            Logger::info(LogChannel::CACHE, 'initTable: SET {{0}} 开始--', [$v['key'], 'data' => $data]);

                            $res = self::loadSet($v['key'], $data, $expire);

                            $temp[$v['key']] = $res;
                        }
                        break;
                    case RedisType::ZSet:
                        if (str_ends_with($v['key'], RedisKeys::DELIMIT)) {
                            foreach ($data as $sk => $sv) {
                                Logger::info(LogChannel::CACHE, 'initTable: ZSET {{0}} 开始--', [$v['key'] . $sk, 'data' => $sv]);

                                $res = self::loadSortedSet($v['key'] . $sk, $sv, $expire);

                                $temp[$v['key'] . $sk] = $res;
                            }
                        } else {
                            Logger::info(LogChannel::CACHE, 'initTable: ZSET {{0}} 开始--', [$v['key'], 'data' => $data]);

                            $res = self::loadSortedSet($v['key'], $data, $expire);

                            $temp[$v['key']] = $res;
                        }
                }
            } catch (Throwable $e) {
                Logger::error(LogChannel::CACHE, 'initTable: {{0}} 数据缓存失败', [$k, 'config' => $v], $e);
                break;
            }

            $list[] = [
                'index' => $k,
                'key' => $v['key'],
                'type' => $v['type'],
                'cache' => $temp
            ];
            Logger::info(LogChannel::CACHE, 'initTable: {{0}} ~~~数据缓存完毕', [$k]);
        }

        Logger::info(LogChannel::CACHE, 'initTable: ~~~本次[{{key}}]缓存初始完毕~~~', compact('key', 'params'));
        return $list;
    }

    /**
     * 检测Table config配置是否正确
     * @param string $key
     * @param array $config
     * @return bool
     */
    private static function checkTableConfig(string $key, array $config): bool
    {
        // 判断key type 等必要参数
        if (!isset($config['key']) || !isset($config['type'])) {
            return false;
        }

        // 判断命令规则
        if (explode(':', $config['key'])[0] !== $key) {
            return false;
        }

        // 判断文件是否存在
        if (!isset($config['class']) || !class_exists($config['class'])) {
            return false;
        }

        // 判断方法是否存在
        if (!isset($config['method']) || !method_exists($config['class'], $config['method'])) {
            return false;
        }

        return true;
    }

    /**
     * 返回key所关联的字符串值
     * @param string $key
     * @param bool $notExistsRefresh
     * @return false|mixed
     * @throws RedisException
     */
    public static function getTable(string $key, bool $notExistsRefresh = false): mixed
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        return self::redis()->get($key);
    }


    /**
     * 返回列表key中指定区间内的元素，区间以偏移量start和stop指定
     * @param string $key
     * @param int $start
     * @param int $end
     * @param bool $notExistsRefresh
     * @return array
     * @throws RedisException
     */
    public static function lRangeTable(string $key, int $start = 0, int $end = -1, bool $notExistsRefresh = false): array
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        return self::redis()->lRange($key, $start, $end);
    }


    /**
     * 返回列表key中，下标为index的元素
     * @param string $key
     * @param int $index
     * @param bool $notExistsRefresh
     * @return mixed
     * @throws RedisException
     */
    public static function lIndexTable(string $key, int $index = 0, bool $notExistsRefresh = false): mixed
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        return self::redis()->lIndex($key, $index);
    }

    /**
     * 返回哈希表key中，一个或多个给定域的值
     * @param string $key
     * @param array $hashKeys
     * @param bool $notExistsRefresh
     * @return array
     * @throws RedisException
     */
    public static function hMGetTable(string $key, array $hashKeys, bool $notExistsRefresh = false): array
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key, $hashKeys);
        }

        return self::redis()->hMGet($key, $hashKeys);
    }

    /**
     * 返回哈希表key中，所有的域和值
     * @param string $key
     * @param bool $notExistsRefresh
     * @return array
     * @throws RedisException
     */
    public static function hGetAllTable(string $key, bool $notExistsRefresh = false): array
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        return self::redis()->hGetAll($key);
    }

    /**
     * 返回哈希表key中给定域field的值
     * @param string $key
     * @param string $hashKey
     * @param bool $notExistsRefresh
     * @return mixed
     * @throws RedisException
     */
    public static function hGetTable(string $key, string $hashKey, bool $notExistsRefresh = false): mixed
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key, [$hashKey]);
        }

        return self::redis()->hGet($key, $hashKey);
    }

    /**
     * 返回集合key中的所有成员
     * @param string $key
     * @param bool $notExistsRefresh
     * @return array
     * @throws RedisException
     */
    public static function sMembersTable(string $key, bool $notExistsRefresh = false): array
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        return self::redis()->sMembers($key);
    }

    /**
     * 判断member元素是否是集合key的成员
     * @param string $key
     * @param $value
     * @return bool
     * @throws RedisException
     */
    public static function sIsMemberTable(string $key, $value): bool
    {
        $res = self::redis()->sIsMember($key, $value);

        // 如果member元素不是集合的成员, 判断key是否存在, 不存在刷新缓存
        if (!$res && !self::redis()->exists($key)) {
            self::initTable($key);

            // 更新缓存后再次判断
            return self::redis()->sIsMember($key, $value);
        }

        // key 若存在直接使用第一第判断查询结果
        return $res;
    }

    /**
     * 返回一个集合的全部成员，该集合是所有给定集合的交集
     * @param string $key
     * @param array $otherKeys
     * @param bool $notExistsRefresh
     * @return array
     * @throws RedisException
     */
    public static function sInterTable(string $key, array $otherKeys, bool $notExistsRefresh = false): array
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        return self::redis()->sInter($key, ...$otherKeys);
    }

    /**
     * 返回一个集合的全部成员，该集合是所有给定集合的差集
     * @param string $key
     * @param array $otherKeys
     * @param bool $notExistsRefresh
     * @return array
     * @throws RedisException
     */
    public static function sDiffTable(string $key, array $otherKeys, bool $notExistsRefresh = false): array
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        return self::redis()->sDiff($key, $otherKeys);
    }

    /**
     * 返回有序集key中，指定区间内的成员
     * @param string $key
     * @param int $start
     * @param int $end
     * @param bool $notExistsRefresh
     * @return array
     * @throws RedisException
     */
    public static function zRangeTable(string $key, int $start = 0, int $end = -1, bool $notExistsRefresh = false): array
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        return self::redis()->zRange($key, $start, $end);
    }

    /**
     * 返回有序集key中，所有score值介于min和max之间(包括等于min或max)的成员
     * @param string $key
     * @param int $min
     * @param int $max
     * @param bool $notExistsRefresh
     * @return array
     * @throws RedisException
     */
    public static function zRangeByScoreTable(string $key, int $min = 0, int $max = -1, bool $notExistsRefresh = false): array
    {
        // 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        return self::redis()->zRangeByScore($key, $min, $max);
    }

    /**
     * 返回有序集key中成员member的排名(从小到大)
     * @param string $key
     * @param $value
     * @param bool $notExistsRefresh
     * @return int|bool
     * @throws RedisException
     */
    public static function zRankTable(string $key, $value, bool $notExistsRefresh = false): bool|int
    {
        // 如果member元素不是集合的成员, 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        // key 若存在直接使用第一第判断查询结果
        return self::redis()->zRank($key, $value);
    }

    /**
     * 返回有序集key中成员member的排名(从大到小)
     * @param string $key
     * @param $value
     * @param bool $notExistsRefresh
     * @return int|bool
     * @throws RedisException
     */
    public static function zRevRankTable(string $key, $value, bool $notExistsRefresh = false): bool|int
    {
        // 如果member元素不是集合的成员, 判断key是否存在, 不存在刷新缓存
        if ($notExistsRefresh && !self::redis()->exists($key)) {
            self::initTable($key);
        }

        // key 若存在直接使用第一第判断查询结果
        return self::redis()->zRevRank($key, $value);
    }

    /**
     * 游标-删除所有的 key 相当于keys
     * @param string $pattern
     * @param int $count
     * @throws RedisException
     */
    public static function delKeysScan(string $pattern, int $count = 100): void
    {
        $iterator = null;
        do {
            $keys = self::redis()->scan($iterator, ['match' => $pattern, 'count' => $count]);
            if (is_array($keys)) {
                $iterator = $keys[0]; // 更新迭代游标
                if (!empty($keys[1])) {
                    self::redis()->del($keys[1]);

                    Logger::info(LogChannel::CACHE, "删除key[{{pattern}}]", compact('pattern', 'keys'));
                }
            }
        } while ($keys && $iterator !== 0);
    }

    /**
     * 游标-获取所有的 key 相当于keys
     * @param null $pattern
     * @param int $count
     * @return array
     * @throws RedisException
     */
    public static function getKeysScan($pattern = null, int $count = 100): array
    {
        $keyArr = array();
        $iterator = null;
        do {
            $keys = self::redis()->scan($iterator, ['match' => $pattern, 'count' => $count]);
            if (is_array($keys)) {
                $iterator = $keys[0]; // 更新迭代游标
                if (!empty($keys[1])) {
                    $keyArr = array_merge($keyArr, $keys[1]);
                }
            }
        } while ($keys && $iterator !== 0);

        return $keyArr;
    }

    /**
     * 将字符串值value关联到key并设置过期时间
     * @param string $key
     * @param mixed $value
     * @param int $timeout 时间秒
     * @return bool
     * @throws RedisException
     */
    public static function set(string $key, mixed $value, int $timeout = 0): bool
    {
        if ($timeout > 0) {
            return self::redis()->setex($key, $timeout, $value);
        }
        return self::redis()->set($key, $value);
    }
}