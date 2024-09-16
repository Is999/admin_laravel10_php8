<?php

namespace App\Jobs;

use App\Enum\LogChannel;
use App\Logging\Logger;
use App\Notifications\TelegramNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendTelegramMessage extends Job
{
    // 超时时间
    public $timeout = 120;

    // chat_id
    protected $chatId = '';

    // message
    protected $message = '';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(mixed $message, string $chatId = '')
    {
        $this->chatId = $chatId ?: env('TELEGRAM_CHAT_ID');
        $this->message = is_string($message) ? $message : json_encode($message, JSON_UNESCAPED_UNICODE);
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // 命令执行 php artisan queue:listen
            Notification::send($this->message, new TelegramNotification($this->chatId));
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, __METHOD__, [$this->chatId], $e);
        }
    }
}
