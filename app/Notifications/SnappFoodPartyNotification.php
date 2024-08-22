<?php

namespace App\Notifications;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use NotificationChannels\Telegram\TelegramFile;

class SnappFoodPartyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $product,
        public string $hashtag,
        public bool $isLast = false,
    ) {}

    public function via($notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram($notifiable)
    {
        $product = $this->product;

        $vendor_url = 'https://snappfood.ir/restaurant/menu/'.$product['vendorCode'];
        $product_url = "$vendor_url?productId=".$product['id'];
        $discount_price = $product['price'] * (100 - $product['discountRatio']) / 100;

        return TelegramFile::create()->parseMode('HTML')
            ->disableNotification(! $this->isLast)
            ->content(
                "🍟 <b>{$product['title']}</b>\n".
                "🍽 <a href=\"{$vendor_url}\">{$product['vendorTypeTitle']} {$product['vendorTitle']}</a>\n".
                (empty($product['rating']) ? '' : '⭐️ '.round($product['rating'], 2).' از '.number_format($product['vote_count'])." نظر\n\n").
                "🛍 {$this->hashtag} <b>{$product['discountRatio']}%</b>\n".
                '💵 <s>'.number_format($product['price']).' ت</s> <b>'.number_format($discount_price)." ت </b>\n".
                '🛵 '.number_format($product['deliveryFee'])." ت\n\n".
                "⌛️ {$product['remaining']} موجود ({$product['capacity']} قابل سفارش، کف ".number_format($product['minOrder']).' ت)'
            )
            ->photo(empty($product['main_image']) ? 'https://raw.githubusercontent.com/ahbanavi/goshne/main/resource/default.jpg' : $product['main_image'])
            ->button('🛍️ خرید محصول', $product_url)
            ->button('🍽 منو '.$product['vendorTypeTitle'], $vendor_url);
    }

    public function middleware(object $notifiable, string $channel): array
    {
        return [new RateLimitedWithRedis('telegram')];
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(10);
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }
}
