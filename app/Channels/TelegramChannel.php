<?php

namespace App\Channels;

use App\Enum\LogChannel;
use App\Logging\Logger;
use Illuminate\Notifications\Notification;
use Throwable;

class TelegramChannel
{
    /**
     * 发送给定通知.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        try {
            $notification->toTelegram($notifiable);
        } catch (Throwable $e) {
            Logger::error(LogChannel::DEV, __METHOD__, [], $e);
        }
    }
}
