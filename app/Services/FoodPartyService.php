<?php

namespace App\Services;

use App\Exceptions\SnappFoodPartyBlockedException;
use App\Models\FoodParty;
use App\Notifications\SnappFoodPartyNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

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
        $home_url = "https://snappfood.ir/search/api/v1/desktop/new-home?lat={$foodParty->latitude}&long={$foodParty->longitude}&optionalClient=WEBSITE&client=WEBSITE&deviceType=WEBSITE&appVersion=8.1.1&locale=fa";

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

        $notifyCache = collect(Redis::hGetAll(config('goshne.ttl.food_party.notify.prefix').$foodParty->id));
        $new_product_hashes = collect();
        foreach ($products as $product) {
            if ($product['discountRatio'] < $foodParty->threshold) {
                continue;
            }

            $discount_price = $product['price'] * (100 - $product['discountRatio']) / 100;
            $product_hash = md5($foodParty->id.$product['id'].$discount_price.$product['vendorCode']);

            if ($notifyCache->has($product_hash)) {
                continue;
            }

            $foodParty->notify(new SnappFoodPartyNotification($product, $party_hashtag));
            $new_product_hashes->push($product_hash);
        }

        if ($new_product_hashes->isNotEmpty()) {
            Redis::transaction(function (\Redis $redis) use ($foodParty, $new_product_hashes) {
                $redis->hmset(config('goshne.ttl.food_party.notify.prefix').$foodParty->id,
                    $new_product_hashes->mapWithKeys(fn ($product_hash) => [$product_hash => 1])->toArray()
                );
                $redis->rawCommand('HEXPIRE', Redis::_prefix(config('goshne.ttl.food_party.notify.prefix').$foodParty->id), config('goshne.ttl.food_party.notify.ttl'), 'NX', 'FIELDS', $new_product_hashes->count(), ...$new_product_hashes->toArray());
            });
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
