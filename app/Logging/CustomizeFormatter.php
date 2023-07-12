<?php
// 定义日志格式

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Illuminate\Log\Logger;

class CustomizeFormatter
{

    /**
     * Customize the given logger instance.
     *
     * @param Logger $logger
     * @return void
     */
    public function __invoke(Logger $logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(
                "[%datetime%] [%level_name%] %message% %context% %extra%\n",
                'Y-m-d H:i:s.u',
                true,
                true
            ));
        }
    }
}
