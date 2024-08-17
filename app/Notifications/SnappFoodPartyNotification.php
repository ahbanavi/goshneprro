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
        public string $hashtag
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
        $diff = $product['price'] - $discount_price;

        return TelegramFile::create()
            ->content(
                "🍟 [{$product['title']}]($product_url) \n".
                "🍽 [{$product['vendorTypeTitle']} {$product['vendorTitle']}]($vendor_url)\n\n".
                "🛍 {$this->hashtag} *{$product['discountRatio']}%*\n".
                '💵 *'.number_format($product['price'])."* ت\n".
                '💸 *'.number_format($discount_price).'* ت ('.number_format($diff)."-)\n".
                '🛵 *'.number_format($product['deliveryFee'])."* ت\n\n".
                '⭐️ '.round($product['rating'], 2).' از '.number_format($product['vote_count'])." رای\n".
                "⌛ {$product['remaining']} تا مونده"
            )
            ->photo($product['main_image'] ?? 'https://raw.githubusercontent.com/ahbanavi/goshne/main/resource/default.jpg')
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
}
