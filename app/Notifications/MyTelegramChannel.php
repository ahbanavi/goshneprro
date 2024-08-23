<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;

class MyTelegramChannel extends TelegramChannel
{
    public function send(mixed $notifiable, Notification $notification, bool $second = false): ?array
    {
        try {
            $rsp = parent::send($notifiable, $notification);
        } catch (\NotificationChannels\Telegram\Exceptions\CouldNotSendNotification $e) {
            if ($e->getMessage() == 'Telegram responded with an error `400 - Bad Request: wrong file identifier/HTTP URL specified`') {
                if (empty($notification->product['main_image']) || $second) {
                    $notification->product['main_image'] = config('goshne.default.image');
                } else {
                    $notification->product['main_image'] = $notification->product['main_image'].'?v='.now()->timestamp;
                }
                $rsp = self::send($notifiable, $notification, true);
            } else {
                throw $e;
            }
        }

        return $rsp;
    }
}
