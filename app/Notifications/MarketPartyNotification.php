<?php

namespace App\Notifications;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use NotificationChannels\Telegram\TelegramFile;

class MarketPartyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $product,
        public array $vendor,
        public bool $isLast = false,
    ) {}

    public function via($notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram($notifiable)
    {
        $product = $this->product;
        $vendor = $this->vendor;

        $vendor_url = 'https://snapp.express/supermarket/%2B/'.$vendor['code'];
        $discount_price = $product['price'] - $product['discount'];

        return TelegramFile::create()->parseMode('HTML')
            ->disableNotification(! $this->isLast)
            ->content(
                "🍎 <b>{$product['title']}</b>\n".
                "🛒 <a href=\"{$vendor_url}\"> {$vendor['title']}</a>".($vendor['isPro'] ? '🌟' : '')."\n".
                (empty($vendor['rating']) ? '' : '⭐️ '.round($vendor['rating'], 2).' از '.number_format($vendor['countReview']).' امتیاز و '.number_format($vendor['commentCount'])." نظر\n").
                "📍 {$vendor['area']}\n\n".
                "🛍 #مارکت‌پارتی <b>{$product['discountRatio']}%</b>\n".
                '💵 <s>'.number_format($product['price']).' ت</s> <b>'.number_format($discount_price)." ت </b>\n".
                '🛵 '.($vendor['isPro'] ? '<s>'.number_format($vendor['deliveryFee']).' ت</s> <b> ارسال رایگان (پرو)</b>' : number_format($vendor['deliveryFee']).' ت')."\n\n".
                "⌛️ {$product['marketPartyCapacity']} موجود ({$product['capacity']} قابل سفارش، کف ".number_format($product['minOrder']).' ت)'
            )
            ->photo(empty($product['mainImage']) ? 'https://raw.githubusercontent.com/ahbanavi/goshne/main/resource/default.jpg' : $product['main_image'])
            ->button('🛒 سوپر مارکت ', $vendor_url);
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
