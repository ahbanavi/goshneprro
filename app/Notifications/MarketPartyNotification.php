<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramFile;

class MarketPartyNotification extends Notification
{
    public function __construct(
        public array $product,
        public array $vendor,
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
            ->photo($product['mainImage'] ?? 'https://raw.githubusercontent.com/ahbanavi/goshne/main/resource/default.jpg')
            ->button('🛒 سوپر مارکت ', $vendor_url);
    }
}
