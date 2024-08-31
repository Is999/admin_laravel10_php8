<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Message;

class TelegramNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $chatId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($chatId = '')
    {
        if (!$chatId) {
            $chatId = env("TELEGRAM_CHAT_ID");
        }
        $this->chatId = $chatId;
        $this->onConnection('redis');
    }

    /**
     * 确定每个通知通道应使用的连接。
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return [
            'telegram' => 'redis',
            'database' => 'sync',
        ];
    }

    /**
     * 确定每个通知通道应使用哪些队列。
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'telegram' => 'telegram-queue'
        ];
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return Message
     */
    public function toTelegram(mixed $notifiable): Message
    {
        return Telegram::sendMessage([
            'chat_id' => $this->chatId,
            'text' => $notifiable,
            'parse_mode' => 'Markdown'
        ]);

    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
