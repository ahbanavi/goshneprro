<?php

namespace App\Services;

use App\Exceptions\SnappFoodPartyBlockedException;
use App\Models\FoodParty;
use App\Notifications\SnappFoodPartyNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FoodPartyService
{
    private static array $headers = [
        'Accept' => 'application/json',
        'Accept-Encoding' => 'gzip',
        'Accept-Language' => 'en-US,en;q=0.5',
        'Connection' => 'keep-alive',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'DNT' => '1',
        'Origin' => 'https://snappfood.ir',
        'Referer' => 'https://snappfood.ir',
        'Sec-Fetch-Dest' => 'empty',
        'Sec-Fetch-Mode' => 'cors',
        'Sec-Fetch-Site' => 'cross-site',
        'TE' => 'trailers',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
    ];

    public static function get(FoodParty $foodParty): bool
    {
        $home_url = "https://snappfood.ir/search/api/v1/desktop/new-home?lat={$foodParty->lat}&long={$foodParty->long}&optionalClient=WEBSITE&client=WEBSITE&deviceType=WEBSITE&appVersion=8.1.1&locale=fa";

        $home_page = Http::withHeaders(static::$headers + ['Host' => 'snappfood.ir'])->get($home_url);

        if ($home_page->status() !== 200) {
            throw_if($home_page->status() === 403, SnappFoodPartyBlockedException::class);
            Log::notice('SnappFoodParty Error not 200: '.$home_page->status().'url: '.$home_url);

            return false;
        }

        $home_data = $home_page->json();

        if (isset($home_data['error'])) {
            Log::notice('SnappFoodParty Error: '.$home_data['error']);

            return false;
        }

        if ($home_data['data']['result'][1]['id'] != 8) {
            return false;
        }

        $party_url = $home_data['data']['result'][1]['data']['url'];

        $party_page = Http::withHeaders(static::$headers + ['Host' => parse_url($party_url)['host']])->get($party_url);
        if ($party_page->status() !== 200) {
            throw_if($party_page->status() === 403, SnappFoodPartyBlockedException::class);
            Log::notice('SnappFoodParty Error not 200: '.$party_page->status().'url: '.$party_url);

            return false;
        }

        $party_data = $party_page->json();

        if (isset($party_data['error'])) {
            Log::notice('SnappFoodParty Error: '.$party_data['error']);

            return false;
        }

        $party_title = $party_data['data']['title'];

        $party_hashtag = '#'.str_replace(' ', '_', $party_title);

        $products = $party_data['data']['products'];

        foreach ($products as $product) {
            if ($product['discountRatio'] < $foodParty->threshold) {
                continue;
            }

            $discount_price = $product['price'] * (100 - $product['discountRatio']) / 100;
            $product_hash = md5($foodParty->id.$product['id'].$discount_price.$product['vendorCode']);

            if (Cache::has($product_hash)) {
                continue;
            }

            Cache::put($product_hash, true, now()->addHours(12));

            $foodParty->notify(new SnappFoodPartyNotification($product, $party_hashtag));
        }

        return true;
    }

    public function __invoke(): void
    {
        $foodParties = FoodParty::active()->get();

        foreach ($foodParties as $foodParty) {
            static::get($foodParty);
        }
    }
}
