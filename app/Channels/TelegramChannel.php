<?php

namespace App\Channels;

use App\Enum\LogChannel;
use App\Logging\Logger;
use Illuminate\Notifications\Notification;

class TelegramChannel
{
    /**
     * 发送给定通知.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            $message = $notification->toTelegram($notifiable);
        } catch (\Throwable $e) {
            Logger::error(LogChannel::DEV, __METHOD__, [], $e);
        }
    }
}