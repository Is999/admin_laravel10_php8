<?php

namespace App\Http\Controllers;

use App\Enum\LogChannel;
use App\Enum\UserAction;
use App\Jobs\SendTelegramMessage;
use App\Logging\Logger;
use App\Services\RedisService;
use App\Services\UserLogService;
use App\Services\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $uid = 0;

    public function setUserLogByUid(int $id)
    {
        $this->uid = $id;
    }

    /**
     * 系统级异常处理
     * @param string $method
     * @param \Throwable $e
     */
    public function systemException(string $method, \Throwable $e): void
    {
        // 发送小飞机消息通知
        $trace = json_encode(array_slice($e->getTrace(), 0, 10), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $d = new \DateTime();
        $message = <<<message
系统错误信息 {$d->format('Y-m-d H:i:s.u')}

``
Method: {$method}
File: {$e->getFile()}:{$e->getLine()}
Code: {$e->getCode()} 
Message: {$e->getMessage()}
``

```json
{$trace}
```
message;
        try {
            SendTelegramMessage::dispatchAfterResponse($message)->onQueue('systemException');
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEFAULT, __METHOD__, [], $e);
        }
    }


    /**
     * redis
     * @return mixed|\Redis
     */
    protected function redis()
    {
        return RedisService::redis();
    }


    /**
     * 添加操作日志
     * @param string $method
     * @param UserAction $action
     * @param string|int $describe
     * @param mixed $data
     * @return void
     */
    public function addUserLog(string $method, UserAction $action, string|int $describe = '', mixed $data = ''): void
    {
        UserLogService::add([
            'action' => $action->value,
            'route' => request()->getMethod() . ':' . request()->route()->getName(),
            'method' => $method,
            'describe' => $describe ? $action->value . ' ' . $describe : $action->value,
            'data' => is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], request()->user ?? ($this->uid ? UserService::getUserInfo($this->uid) : []));
    }
}
