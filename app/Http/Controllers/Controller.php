<?php

namespace App\Http\Controllers;

use App\Enum\LogChannel;
use App\Enum\UserAction;
use App\Jobs\SendTelegramMessage;
use App\Logging\Logger;
use App\Services\Service;
use App\Services\UserLogService;
use App\Services\UserService;
use DateTime;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Routing\Controller as BaseController;
use Redis;
use RedisException;
use Throwable;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private int $uid = 0;

    public function setUserLogByUid(int $id): void
    {
        $this->uid = $id;
    }

    /**
     * 系统级异常处理
     * @param string $method
     * @param Throwable $e
     */
    public function systemException(string $method, Throwable $e): void
    {
        // 发送小飞机消息通知
        $uuid = (new DateTime)->format('Y-m-d H:i:s.u-') . rand(100000, 999999);
        $message = <<<message
异常信息 {$uuid}

``
Method: {$method}

File: {$e->getFile()}:{$e->getLine()}

Code: {$e->getCode()} 

Message: {$e->getMessage()}
``
message;
        try {
            // 由于消息太长会发送失败，大于最大长度的消息这里进行消息拆分发送
            $trace = array_slice($e->getTrace(), 0, 10); // 获取前10条记录
            foreach ($trace as $k => $v) {
                $item = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $traceMessage = <<<message

Trace-{$k}
```json
{$item}
```
message;

                // 超过长度发送消息，未超过长度合并消息
                if (strlen($traceMessage) + strlen($message) > 4096) {
                    SendTelegramMessage::dispatchAfterResponse($message)->onQueue('systemException');
                    $message = $uuid . PHP_EOL . $traceMessage; // 发送后重置消息内容
                } else {
                    $message .= $traceMessage; // 合并消息内容
                }

                // 最后一次直接发送消息
                if ($k == count($trace) - 1) {
                    SendTelegramMessage::dispatchAfterResponse($message)->onQueue('systemException');
                    break;
                }
            }
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
        }
    }

    /**
     * redis 连接
     * @param string|null $name
     */
    protected function redis(string $name = null): Connection|Redis
    {
        return Service::redis($name);
    }


    /**
     * 添加操作日志
     * @param string $method
     * @param UserAction $action
     * @param string|int $describe
     * @param mixed $data
     * @return void
     * @throws RedisException
     */
    public function addUserLog(string $method, UserAction $action, string|int $describe = '', mixed $data = ''): void
    {
        UserLogService::add([
            'action' => $action->value,
            'route' => request()->getMethod() . ':' . request()->route()->getName(),
            'method' => $method,
            'describe' => $describe ? $action->value . ' ' . $describe : $action->value,
            'data' => is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], request()->user ?? ($this->uid ? (new UserService)->getUserInfo($this->uid) : []));
    }
}
