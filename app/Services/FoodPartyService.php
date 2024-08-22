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

    public static function get(FoodParty $foodParty): int
    {
        $party_base_url = "https://foodparty.zoodfood.com/676858d198d35e7713a47e66ba0755c8/mobile-offers/{$foodParty->latitude}/{$foodParty->longitude}";
        $super_types = $foodParty->super_types;

        if (empty($super_types)) {
            return 0;
        }

        $all_products = collect();
        foreach ($super_types as $sueper_type) {
            $party_url = $party_base_url."?superType={$sueper_type}";
            $party_page = Http::withHeaders(static::$headers + ['Host' => parse_url($party_url)['host']])->get($party_url);
            if ($party_page->status() !== 200) {
                throw_if($party_page->status() === 403, SnappFoodPartyBlockedException::class);
                Log::notice('SnappFoodParty Error not 200: '.$party_page->status().'url: '.$party_url);

                return -1;
            }

            $party_data = $party_page->json();

            if (isset($party_data['error'])) {
                Log::notice('SnappFoodParty Error: '.$party_data['error']);

                return -1;
            }

            $party_title = $party_data['data']['title'];
            $party_hashtag = '#'.str_replace(' ', '‌', $party_title);

            $products = $party_data['data']['products'];

            if (empty($products)) {
                continue;
            }

            $all_products->push([
                'products' => $products,
                'party_hashtag' => $party_hashtag,
            ]);
        }

        if ($all_products->isEmpty()) {
            return 0;
        }

        $notifyCache = collect(Redis::hGetAll(config('goshne.ttl.food_party.notify.prefix').$foodParty->id));
        $new_product_hashes = collect();
        $new_products = collect();
        $all_products->each(function ($super_type_products) use ($foodParty, $notifyCache, $new_product_hashes, $new_products) {
            $products = $super_type_products['products'];
            $party_hashtag = $super_type_products['party_hashtag'];

            foreach ($products as $product) {
                $discount_price = $product['price'] * (100 - $product['discountRatio']) / 100;
                $product_hash = md5($foodParty->id.$product['id'].$discount_price.$product['vendorCode']);

                if ($notifyCache->has($product_hash)) {
                    continue;
                }

                if ($product['discountRatio'] >= $foodParty->threshold
                    || collect($foodParty->vendors)->contains(
                        fn ($vendor) => $vendor['c'] == $product['vendorCode'] && $product['discountRatio'] >= $vendor['t']
                    )
                ) {
                    $new_products->push([
                        'product' => $product,
                        'party_hashtag' => $party_hashtag,
                    ]);
                    $new_product_hashes->push($product_hash);
                }
            }
        });

        $new_products->each(function ($item, $index) use ($foodParty, $new_products) {
            $foodParty->notify((new SnappFoodPartyNotification(product: $item['product'], hashtag: $item['party_hashtag'], isLast: $item === $new_products->last()))->delay($index));
        });

        if ($new_product_hashes->isNotEmpty()) {
            Redis::transaction(function (\Redis $redis) use ($foodParty, $new_product_hashes) {
                $redis->hmset(config('goshne.ttl.food_party.notify.prefix').$foodParty->id,
                    $new_product_hashes->mapWithKeys(fn ($product_hash) => [$product_hash => 1])->toArray()
                );
                $redis->rawCommand('HEXPIRE', Redis::_prefix(config('goshne.ttl.food_party.notify.prefix').$foodParty->id), config('goshne.ttl.food_party.notify.ttl'), 'NX', 'FIELDS', $new_product_hashes->count(), ...$new_product_hashes->toArray());
            });
        }

        return $new_product_hashes->count();
    }

    public static function cacheLen(FoodParty $foodParty): int
    {
        return Redis::hLen(config('goshne.ttl.food_party.notify.prefix').$foodParty->id);
    }

    public static function clearCache(FoodParty $foodParty): bool
    {
        return Redis::del(config('goshne.ttl.food_party.notify.prefix').$foodParty->id) !== false;
    }

    public function __invoke(): void
    {
        $foodParties = FoodParty::active()->get();

        foreach ($foodParties as $foodParty) {
            static::get($foodParty);
        }
    }
}
