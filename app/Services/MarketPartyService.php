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
        'accept' => '*/*',
        'accept-language' => 'en-US,en;q=0.9,fa;q=0.8,nl;q=0.7,pt;q=0.6',
        'content-type' => 'application/json',
        'priority' => 'u=1, i',
        'sec-ch-ua' => '"Chromium";v="128", "Not;A=Brand";v="24", "Microsoft Edge";v="128"',
        'sec-ch-ua-mobile' => '?0',
        'sec-ch-ua-platform' => '"Windows"',
        'sec-fetch-dest' => 'empty',
        'sec-fetch-mode' => 'cors',
        'sec-fetch-site' => 'same-origin',
        'Host' => 'snapp.express',
        'Origin' => 'https://snapp.express',
        'Referer' => 'https://snapp.express/marketparty-list',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'DNT' => '1',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0',
    ];

    private static string $url = 'https://snapp.express/api';

    public static function get(MarketParty $marketParty): int
    {
        $headers = static::$headers;
        $headers['x-metadata'] = json_encode(['lat' => $marketParty->latitude, 'long' => $marketParty->longitude]);

        $vendor_page = Http::withHeaders($headers)
            ->withBody('{"operationName":"getVendorList","variables":{"variable":"-1","page":0,"pageSize":1000,"filters":{"superType":[4],"mode":"CURRENT","item_position":"homePage"}},"query":"query getVendorList($variable: String, $page: Int, $pageSize: Int, $filters: JSONObject) {\n  vendorList(\n    variable: $variable\n    page: $page\n    pageSize: $pageSize\n    filters: $filters\n  ) {\n    status\n    data {\n      count\n      openCount\n      extraSections {\n        filters {\n          top {\n            data {\n              title\n              value\n              __typename\n            }\n            __typename\n          }\n          sections {\n            data {\n              title\n              value\n              __typename\n            }\n            __typename\n          }\n          __typename\n        }\n        __typename\n      }\n      finalResult {\n        data {\n          id\n          title\n          isMarketParty\n          backgroundImage\n          commentCount\n          minimumOrderValue\n          code\n          status\n          area\n          countReview\n          isExpressPin\n          logo\n          isOpen\n          preOrderEnabled\n          deliveryFee\n          deliveryTime\n          discountValueForView\n          rating\n          rate\n          hasCoupon\n          bestCoupon\n          couponCount\n          isPro\n          deliveryTypes {\n            hasExpress\n            hasPickup\n            hasSlow\n            hasVendor\n            __typename\n          }\n          __typename\n        }\n        type\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}"}')
            ->post(static::$url);

        if ($vendor_page->status() !== 200) {
            throw_if($vendor_page->status() === 403, MarketPartyBlockedException::class);
            Log::notice('MarketParty Error not 200: '.$vendor_page->status());

            return -1;
        }

        $vendors = $vendor_page->json('data.vendorList.data.finalResult');

        if (empty($vendors)) {
            return 0;
        }

        $notifyCache = collect(Redis::hGetAll(config('goshne.ttl.market_party.notify.prefix').$marketParty->id));
        $new_product_hashes = collect();
        $new_products = collect();
        foreach ($vendors as $vendor) {
            $products = Cache::remember(config('goshne.ttl.market_party.products.prefix').$vendor['data']['code'], config('goshne.ttl.market_party.products.ttl'), function () use ($headers, $vendor) {
                $vendor_party_page = Http::withHeaders($headers)
                    ->withBody('{"operationName":"getSuperMarketMarketParty","variables":{"variable":"'.$vendor['data']['code'].'","page":0,"pageSize":1000},"query":"query getSuperMarketMarketParty($variable: String, $page: Int, $pageSize: Int) {\n  superMarketMarketParty(variable: $variable, page: $page, pageSize: $pageSize) {\n    errors {\n      field\n      message\n      __typename\n    }\n    status\n    data {\n      title\n      firstActivePeriodStartRFC\n      firstActivePeriodEndRFC\n      currentTimeRFC\n      activePeriodTitle\n      inactivePeriodTitle\n      capacityPerOrder\n      config {\n        coverImage\n        moreImage\n        mainImage\n        backgroundColor\n        textColor\n        __typename\n      }\n      products {\n        totalCount\n        pageSize\n        list {\n          id\n          productVariationId\n          price\n          discountRatio\n          productVariationTitle\n          deliveryFee\n          stock\n          title\n          discount\n          image\n          vendorCode\n          vendorId\n          capacity\n          vendorTitle\n          description\n          mainImage\n          minOrder\n          totalStock\n          menuCategoryId\n          isSpecialBackend\n          isSpecial\n          isMarketParty\n          marketPartyCapacity\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}"}')
                    ->post(static::$url);

                if ($vendor_party_page->status() !== 200) {
                    throw_if($vendor_party_page->status() === 403, MarketPartyBlockedException::class);
                    Log::notice('SnappFoodParty Error not 200: '.$vendor_party_page->status());

                    return null;
                }

                return $vendor_party_page->json('data.superMarketMarketParty.data.products.list');
            });

            if (empty($products)) {
                continue;
            }

            foreach ($products as $product) {
                $discount_price = $product['price'] - $product['discount'];
                $product_hash = md5($product['id'].$discount_price.$product['vendorCode']);

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
                        'vendor' => $vendor['data'],
                    ]);
                    $new_product_hashes->push($product_hash);
                }
            }
        }

        $sorted = $new_products->groupBy(fn ($item) => $item['product']['title'])
            ->map(fn ($group) => $group->sortBy([
                ['vendor.isPro', 'desc'],
                ['product.discountRatio', 'desc'],
            ])->take(
                $marketParty->max_item === 0 ? $group->count() : $marketParty->max_item
            ))->flatten(1);

        $sorted->each(function ($item) use ($marketParty, $sorted) {
            $marketParty->notify(new MarketPartyNotification(product: $item['product'], vendor: $item['vendor'], isLast: $item === $sorted->last()));
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
