<?php

namespace App\Services;

use App\Exceptions\MarketPartyBlockedException;
use App\Models\MarketParty;
use App\Notifications\MarketPartyNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class MarketPartyService
{
    private static array $headers = [
        'Host' => 'api.snapp.express',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0',
        'Accept' => 'application/json, text/plain, */*',
        'Accept-Language' => 'fa-IR, fa;q=0.9,en;q=0.8,*;q=0.1',
        'Accept-Encoding' => 'gzip, deflate, br, zstd',
        'Origin' => 'https://express.snapp.market',
        'DNT' => '1',
        'Connection' => 'keep-alive',
        'Referer' => 'https://express.snapp.market/',
        'Sec-Fetch-Dest' => 'empty',
        'Sec-Fetch-Mode' => 'cors',
        'Sec-Fetch-Site' => 'cross-site',
        'TE' => 'trailers',
    ];

    private static string $url = 'https://api.snapp.express';

    public static function get(MarketParty $marketParty): int
    {
        $params = [
            'page' => 0,
            'page_size' => 1000,
            'extra-filter' => [
                'vendor_collection' => -1
            ],
            'superType' => [4],
            'mode' => 'CURRENT',
            'item_position' => 'homePage',
            'client' => 'PWA',
            'deviceType' => 'DESKTOP',
            'appVersion' => '1.157.7',
            'lat' => $marketParty->latitude,
            'long' => $marketParty->longitude
        ];

        $vendor_page = Http::withHeaders(static::$headers)
            ->get(static::$url . '/express-vendor/general/vendors-list', $params);

        if ($vendor_page->status() !== 200) {
            throw_if($vendor_page->status() === 403, MarketPartyBlockedException::class);
            Log::notice('MarketParty Error not 200: '.$vendor_page->status());
            return -1;
        }

        $vendors = collect($vendor_page->json('data.finalResult'))
        ->filter(fn($item) => $item['type'] === 'VENDOR')
        ->map(fn($item) => $item['data']);

        if ($vendors->isEmpty()) {
            return 0;
        }

        $notifyCache = collect(Redis::hGetAll(config('goshne.ttl.market_party.notify.prefix').$marketParty->id));
        $new_product_hashes = collect();
        $new_products = collect();
        foreach ($vendors as $vendor) {

            $products = Cache::remember(config('goshne.ttl.market_party.products.prefix').$vendor['code'], config('goshne.ttl.market_party.products.ttl'), function () use ($vendor, $marketParty) {
                $params = [
                    'variable' => $vendor['code'],
                    'page_size' => 1000,
                    'client' => 'PWA',
                    'deviceType' => 'DESKTOP',
                    'appVersion' => '1.157.7',
                    'lat' => $marketParty->latitude,
                    'long' => $marketParty->longitude
                ];

                $vendor_party_page = Http::withHeaders(static::$headers)
                    ->get(static::$url . '/market-party/' . $vendor['code'], $params);

                if ($vendor_party_page->status() !== 200) {
                    throw_if($vendor_party_page->status() === 403, MarketPartyBlockedException::class);
                    Log::notice('SnappFoodParty Error not 200: '.$vendor_party_page->status());
                    return null;
                }

                return $vendor_party_page->json('data.products.List');
            });



            if (empty($products)) {
                continue;
            }

            foreach ($products as $product) {
                $discount_price = $product['price'] - $product['discount'];
                $product_hash = md5($product['productVariationId'].$discount_price.$product['vendorCode']);

                if ($notifyCache->has($product_hash)) {
                    continue;
                }

                if ($product['discountRatio'] >= $marketParty->threshold
                    || collect($marketParty->products)->contains(
                        fn ($pattern) => Str::is($pattern['n'], $product['title']) && $product['discountRatio'] >= $pattern['t']
                    )
                ) {
                    $new_products->push([
                        'product' => $product,
                        'vendor' => $vendor,
                        'total_price' => $discount_price + ($vendor['is_pro'] ? 0 : $vendor['deliveryFee']),
                        'min_capacity' => min($product['capacity'], $product['stock']),
                    ]);
                    $new_product_hashes->push($product_hash);
                }
            }
        }

        $sorted = $new_products->groupBy(fn ($item) => $item['product']['title'])
            ->map(fn ($group) => $group->sortBy([
                ['total_price', 'asc'],
                ['min_capacity', 'desc'],
                ['product.minOrder', 'asc'],
            ])->take(
                $marketParty->max_item === 0 ? $group->count() : $marketParty->max_item
            ))->flatten(1);

        $sorted->each(function ($item) use ($marketParty, $sorted) {
            $marketParty->notify((new MarketPartyNotification(product: $item['product'], vendor: $item['vendor'], isLast: $item === $sorted->last())));
        });

        if ($new_product_hashes->isNotEmpty()) {
            Redis::transaction(function (\Redis $redis) use ($marketParty, $new_product_hashes) {
                $redis->hmset(config('goshne.ttl.market_party.notify.prefix').$marketParty->id,
                    $new_product_hashes->mapWithKeys(fn ($product_hash) => [$product_hash => 1])->toArray()
                );
                $redis->rawCommand('HEXPIRE', Redis::_prefix(config('goshne.ttl.market_party.notify.prefix').$marketParty->id), config('goshne.ttl.market_party.notify.ttl'), 'NX', 'FIELDS', $new_product_hashes->count(), ...$new_product_hashes->toArray());
            });
        }

        return $sorted->count();
    }

    public static function cacheLen(MarketParty $marketParty): int
    {
        return Redis::hLen(config('goshne.ttl.market_party.notify.prefix').$marketParty->id);
    }

    public static function clearCache(MarketParty $marketParty): bool
    {
        return Redis::del(config('goshne.ttl.market_party.notify.prefix').$marketParty->id) !== false;
    }

    public function __invoke(): void
    {
        $marketParties = MarketParty::active()->get();

        foreach ($marketParties as $marketParty) {
            static::get($marketParty);
        }
    }
}
