<?php
/**
 * ----------------------------------------
 * 使用示例:
 * 这里只定义了 error、 warning、notice、 info、 debug 函数
 * 其它未定义的日志函数通过 __callStatic 函数调用 laravel log 函数
 * 支持 emergency、 alert、 critical、 error、 warning、 notice、 info、 debug
 * warning 及其之上的日志级别支持第四个参数: 实现Throwable接口的异常对象
 *
 * ----------------------------------------
 * 示例1: Logger::debug(LogChannel::DEFAULT, '{{name}}正在{{doing}}', ['name' => '小明', 'doing'=>'玩游戏']);
 * ----------------------------------------
 * 示例2: Logger::info(LogChannel::DEV, '{{0}}正在{{1}}', ['小明',  '读书']);
 * ----------------------------------------
 * 示例3: Logger::critical(LogChannel::DEV, '{{0}}正在{{1}}', ['小明',  '读书']);
 */

namespace App\Logging;

use App\Enum\LogChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class Logger
{
    /**
     * error 日志
     * @param LogChannel $channel
     * @param string $msg
     * @param array $data
     * @param Throwable|null $error
     */
    public static function error(LogChannel $channel, string $msg = '', array $data = [], Throwable $error = null): void
    {
        $data['extra']['dir'] = self::dir($channel);
        if ($error instanceof Throwable) {
            $data['extra']['err'] = self::getErr($error);
        }
        self::write(__FUNCTION__, $channel, $msg, $data);
    }

    /**
     * warning 日志
     * @param LogChannel $channel
     * @param string $msg
     * @param array $data
     * @param Throwable|null $error
     */
    public static function warning(LogChannel $channel, string $msg = '', array $data = [], Throwable $error = null): void
    {
        $data['extra']['dir'] = self::dir($channel);
        if ($error instanceof Throwable) {
            $data['extra']['err'] = self::getErr($error);
        }
        self::write(__FUNCTION__, $channel, $msg, $data);
    }

    /**
     * notice 日志
     * @param LogChannel $channel
     * @param string $msg
     * @param array $data
     * @param Throwable|null $error
     */
    public static function notice(LogChannel $channel, string $msg = '', array $data = [], Throwable $error = null): void
    {
        $data['extra']['dir'] = self::dir($channel);
        if ($error instanceof Throwable) {
            $data['extra']['err'] = self::getErr($error);
        }
        self::write(__FUNCTION__, $channel, $msg, $data);
    }

    /**
     * info 日志
     * @param LogChannel $channel
     * @param string $msg
     * @param array $data
     */
    public static function info(LogChannel $channel, string $msg = '', array $data = []): void
    {
        $data['extra']['dir'] = self::dir($channel);
        self::write(__FUNCTION__, $channel, $msg, $data);
    }

    /**
     * debug 日志
     * @param LogChannel $channel
     * @param string $msg
     * @param array $data
     */
    public static function debug(LogChannel $channel, string $msg = '', array $data = []): void
    {
        $data['extra']['dir'] = self::dir($channel);
        self::write(__FUNCTION__, $channel, $msg, $data);
    }

    /**
     * 关联上下文
     * @param LogChannel $channel
     * @param array $context
     * @return \Illuminate\Log\Logger
     */
    public static function withContext(LogChannel $channel, array $context): \Illuminate\Log\Logger
    {
        if ($channel != LogChannel::DEFAULT && $channel->value != config('logging.default')) {
            if (!empty(config('logging.channels.' . $channel->value))) {
                return Log::channel($channel->value)->withContext($context);
            }
        }

        // 默认配置通道
        return Log::withContext($context);
    }

    /**
     * write
     * @param string $level
     * @param LogChannel $channel
     * @param string $msg
     * @param array $data
     */
    private static function write(string $level, LogChannel $channel, string $msg, array $data): void
    {
        try {
            while (Str::containsAll($msg, ['{{', '}}'])) { // 匹配是否包含{{}}
                $key = Str::of($msg)->betweenFirst('{{', '}}')->value(); // 取出{{x}} 中的 x
                if ($key !== "" && isset($data[$key]) && (is_string($data[$key]) || is_numeric($data[$key]))) {
                    $msg = Str::replace('{{' . $key . '}}', $data[$key], $msg);
                    unset($data[$key]); // 替换后删除该数据
                } else {
                    // 匹配不到数据把{{XX}}替换成{\{XX}\}, 避免成为死循环
                    $msg = Str::replaceFirst('{{', '{\{', $msg);
                    $msg = Str::replaceFirst('}}', '}\}', $msg);
                }
            }
        } catch (Throwable $e) {
            // 捕获数据类型导致的异常
            Log::channel($channel->value)->error('日志记录转换失败', compact('msg', 'data', 'e'));
        }

        // 非默认配置通道
        if ($channel != LogChannel::DEFAULT && $channel->value != config('logging.default')) {
            if (!empty(config('logging.channels.' . $channel->value))) {
                Log::channel($channel->value)->{$level}($msg, $data);
                return;
            }
        }

        // 默认配置通道
        Log::{$level}($msg, $data);
    }


    /**
     * 打印日志文件信息
     * @param LogChannel $channel
     * @return string
     */
    private static function dir(LogChannel $channel): string
    {
        try {
            // sql 日志获取查询sql 的地方
            if ($channel == LogChannel::SQL) {
                return self::sqlDir();
            }

            $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

            $function = $traces[2]['function']; // 打印日志所在的方法
            $class = $traces[2]['class'] ?? $traces[2]['file'];// 打印日志所在的类
            $line = $traces[1]['line']; // 打印日志所在的行号

            return $function . ' ' . $class . ':' . $line;
        } catch (Throwable $e) {
            Log::channel($channel->value)->error('App\Logging\Logger getDir error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * 执行sql 的文件信息
     * @return string
     */
    private static function sqlDir(): string
    {
        $collections = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 25))->skip(10);//跳过前10个

        // 获取第一个非 vendor 文件 下标
        $key = 0;
        foreach ($collections->toArray() as $k => $v) {
            if (str_contains($v['file'], '/vendor/')) {
                continue;
            }
            $key = $k;
            break;
        }

        if ($key != 0) {
            $traces = $collections->toArray();
            $function = $traces[$key + 1]['function']; // 打印日志所在的方法
            $class = $traces[$key + 1]['class'] ?? $traces[$key]['file'];// 打印日志所在的类
            $line = $traces[$key]['line']; // 打印日志所在的行号
            return $function . ' ' . $class . ':' . $line;
        }
        return '';
    }

    /**
     * 异常信息
     * @param Throwable $e
     * @return array
     */
    private static function getErr(Throwable $e): array
    {
        return [
            'file' => $e->getFile() . ':' . $e->getLine(),
            'code' => $e->getCode(),
            'msg' => $e->getMessage(),
        ];
    }

    /**
     * 最少需要两个参数
     * 第一个参数: LogChannel $channel 日志通道名称
     * 第二个参数: message  string
     * 第三个参数: data array
     * 第四个参数: 实现 Throwable 接口的对象
     * @param $name : emergency、 alert、 critical、 error、 warning、 notice、 info、 debug
     * @param  $arguments
     */
    public static function __callStatic($name, $arguments)
    {
        if (count($arguments) >= 2 && $arguments[0] instanceof LogChannel && is_string($arguments[1])) {
            if (!(isset($arguments[2]) && is_array($arguments[2]))) {
                $arguments[2] = [];
            }
            $arguments[2]['extra']['dir'] = self::dir($arguments[0]);

            // 添加错误消息
            if (isset($arguments[3]) && ($arguments[3] instanceof Throwable)) {
                $arguments[2]['extra']['err'] = self::getErr($arguments[3]);
            }
            self::write($name, ...$arguments);
        }
    }
}
