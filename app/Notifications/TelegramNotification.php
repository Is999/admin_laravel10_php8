<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Message;

class TelegramNotification extends Notification
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
            $chatId = env("TELEGRAM_CHAT_ID", null);
        }
        $this->chatId = $chatId;
    }


    /**
     * 确定每个通知通道应使用哪些队列。
     *
     * @return array
     */
//    public function viaQueues()
//    {
//        return [
//            'telegram' => 'telegram-queue'
//        ];
//    }


    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     */
    public function via($notifiable)
    {
        return TelegramChannel::class;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return Message
     */
    public function toTelegram(mixed $notifiable)
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
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
