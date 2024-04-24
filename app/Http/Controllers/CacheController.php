<?php

namespace App\Http\Controllers;

use App\Enum\Code;
use App\Enum\LogChannel;
use App\Enum\RedisKeys;
use App\Enum\RedisType;
use App\Enum\UserAction;
use App\Exceptions\CustomizeException;
use App\Logging\Logger;
use App\Services\RedisService;
use App\Services\ResponseService as Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Throwable;

class CacheController extends Controller
{

    /**
     * 列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $items = [];
            $config = config(RedisService::TABLE_CONFIG_FILE);
            foreach ($config as $k => $v) {
                $v['keyTitle'] = $v['key'];
                if (isset($v['combine']) && $v['combine']) {
                    if (is_array($v['combine'])) {
                        $v['keyTitle'] .= '{' . implode('}' . RedisKeys::DELIMIT . '{', $v['combine']) . '}';
                    } else {
                        $v['keyTitle'] .= '{' . $v['combine'] . '}';
                    }
                }
                $v['index'] = $k;
                $items[] = $v;
            }
            return Response::success(['items' => $items, 'total' => count($items)]);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 刷新
     * @param Request $request
     * @return JsonResponse
     */
    public function renew(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->input()
                , [
                    'key' => 'required|string',
                    'type' => [ // 类型: 1 菜单, 0 目录
                        'required',
                        new Enum(RedisType::class),
                    ],
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();

            // 刷新缓存
            $result = RedisService::initTable($input['key']);
            if (!$result) {
                throw new CustomizeException(Code::F2001);
            }

            // 记录日志
            $this->addUserLog(__FUNCTION__, UserAction::RENEW_CACHE, 'key=' . $input['key'], $input);
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
     * 刷新全部
     * @param Request $request
     * @return JsonResponse
     */
    public function renewAll(Request $request): JsonResponse
    {
        try {
            // 谨慎使用该操作避免造成缓存雪崩
            $result = RedisService::initTable();
            if (!$result) {
                throw new CustomizeException(Code::F2001);
            }

            // 记录操作日志
            $this->addUserLog(__FUNCTION__, UserAction::RENEW_All_CACHE);
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
     * 信息
     * @param Request $request
     * @return JsonResponse
     */
    public function serverInfo(Request $request): JsonResponse
    {
        try {
            $redis = $this->redis();
            $result = $redis->info();

            return Response::success((array)$result);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 查看缓存key信息
     * @param Request $request
     * @return JsonResponse
     */
    public function keyInfo(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->input()
                , [
                    'key' => 'required|string',
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();
            $key = $input['key'];
            $redis = $this->redis();
            $type = $redis->type($input['key']);
            $total = 1;
            $value = null;
            switch ($type) {
                case RedisType::String->value:
                    $value = $redis->get($key);
                    break;
                case RedisType::Set->value:
                    $total = $redis->sCard($key);
                    $value = $redis->sMembers($key);
                    break;
            }
            $ttl = $redis->ttl($input['key']);

            return Response::success(compact('key', 'type', 'ttl', 'total', 'value'));
        } catch (CustomizeException $e) {
            return Response::fail($e->getCode(), $e->getMessage());
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 搜索key
     * @param Request $request
     * @return JsonResponse
     */
    public function searchKey(Request $request): JsonResponse
    {
        try {
            // 验证参数
            $validator = Validator::make($request->input()
                , [
                    'key' => 'required|string',
                ]);
            if ($validator->fails()) {
                throw new CustomizeException(Code::FAIL, $validator->errors()->first());
            }

            $input = $validator->validated();
            $result = $this->Scan($input['key']);
            return Response::success($result);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
            $this->systemException(__METHOD__, $e);
            return Response::fail(Code::SYSTEM_ERR);
        }
    }

    /**
     * 匹配key
     * @param string $pattern
     * @param int $count
     * @return array
     */
    public function Scan(string $pattern = '*', int $count = 100): array
    {
        $keyArr = array();
        $iterator = null;
        do {
            $keys = $this->redis()->scan($iterator, ['match' => $pattern, 'count' => $count]);
            if (is_array($keys)) {
                $iterator = $keys[0]; // 更新迭代游标
                if (!empty($keys[1])) {
                    $keyArr = array_merge($keyArr, $keys[1]);
                }
            }
        } while ($keys && $iterator !== 0);

        return $keyArr;
    }
}