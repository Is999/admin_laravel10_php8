<?php

namespace App\Providers;

use App\Enum\LogChannel;
use App\Logging\Logger;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Database\Events\QueryExecuted;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // sql 日志
        try {
            if (env('DB_LOG')) {
                DB::listen(function (QueryExecuted $query) {
                    $sql = $query->sql;
                    foreach ($query->bindings as $key => $value) {
                        if ($value instanceof DateTime) {
                            $value = $value->format('Y-m-d H:i:s');
                        }
                        $rkey = is_numeric($key) ? '?' : ':' . $key;
                        $sql = Str::replaceFirst($rkey, "'$value'", $sql);
                    }

                    # 记录慢sql日志时间单位（毫秒）
                    if ($query->time > (int)env('DB_SLOW_TIME', 2000)) { // 添加慢日志
                        Logger::warning(LogChannel::SQL, sprintf('[%s] %s', $query->time, $sql));
                        return;
                    }
                    Logger::info(LogChannel::SQL, sprintf('[%s] %s', $query->time, $sql));
                });
            }
        } catch (Exception $e) {
            Logger::error(LogChannel::DEFAULT, 'sql log 写入失败', [], $e);
        }

    }
}
