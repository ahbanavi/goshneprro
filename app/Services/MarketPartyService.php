<?php

namespace App\Services;

use App\Exceptions\MarketPartyBlockedException;
use App\Models\MarketParty;
use App\Notifications\MarketPartyNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

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

    public static function get(MarketParty $marketParty): bool
    {
        $cache = collect(Redis::hGetAll('mp_'.$marketParty->id));

        $headers = static::$headers;
        $headers['x-metadata'] = json_encode(['lat' => $marketParty->latitude, 'long' => $marketParty->longitude]);

        $vendor_page = Http::withHeaders($headers)
            ->withBody('{"operationName":"getVendorList","variables":{"variable":"-1","page":0,"pageSize":1000,"filters":{"superType":[4],"mode":"CURRENT","item_position":"homePage"}},"query":"query getVendorList($variable: String, $page: Int, $pageSize: Int, $filters: JSONObject) {\n  vendorList(\n    variable: $variable\n    page: $page\n    pageSize: $pageSize\n    filters: $filters\n  ) {\n    status\n    data {\n      count\n      openCount\n      extraSections {\n        filters {\n          top {\n            data {\n              title\n              value\n              __typename\n            }\n            __typename\n          }\n          sections {\n            data {\n              title\n              value\n              __typename\n            }\n            __typename\n          }\n          __typename\n        }\n        __typename\n      }\n      finalResult {\n        data {\n          id\n          title\n          isMarketParty\n          backgroundImage\n          commentCount\n          minimumOrderValue\n          code\n          status\n          area\n          countReview\n          isExpressPin\n          logo\n          isOpen\n          preOrderEnabled\n          deliveryFee\n          deliveryTime\n          discountValueForView\n          rating\n          rate\n          hasCoupon\n          bestCoupon\n          couponCount\n          isPro\n          deliveryTypes {\n            hasExpress\n            hasPickup\n            hasSlow\n            hasVendor\n            __typename\n          }\n          __typename\n        }\n        type\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}"}')
            ->post(static::$url);

        if ($vendor_page->status() !== 200) {
            throw_if($vendor_page->status() === 403, MarketPartyBlockedException::class);
            Log::notice('MarketParty Error not 200: '.$vendor_page->status());

            return false;
        }

        $vendors = $vendor_page->json('data.vendorList.data.finalResult');

        if (empty($vendors)) {
            return false;
        }

        foreach ($vendors as $vendor) {
            $vendor_party_page = Http::withHeaders($headers)
                ->withBody('{"operationName":"getSuperMarketMarketParty","variables":{"variable":"'.$vendor['data']['code'].'","page":0,"pageSize":1000},"query":"query getSuperMarketMarketParty($variable: String, $page: Int, $pageSize: Int) {\n  superMarketMarketParty(variable: $variable, page: $page, pageSize: $pageSize) {\n    errors {\n      field\n      message\n      __typename\n    }\n    status\n    data {\n      title\n      firstActivePeriodStartRFC\n      firstActivePeriodEndRFC\n      currentTimeRFC\n      activePeriodTitle\n      inactivePeriodTitle\n      capacityPerOrder\n      config {\n        coverImage\n        moreImage\n        mainImage\n        backgroundColor\n        textColor\n        __typename\n      }\n      products {\n        totalCount\n        pageSize\n        list {\n          id\n          productVariationId\n          price\n          discountRatio\n          productVariationTitle\n          deliveryFee\n          stock\n          title\n          discount\n          image\n          vendorCode\n          vendorId\n          capacity\n          vendorTitle\n          description\n          mainImage\n          minOrder\n          totalStock\n          menuCategoryId\n          isSpecialBackend\n          isSpecial\n          isMarketParty\n          marketPartyCapacity\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}"}')
                ->post(static::$url);

            if ($vendor_party_page->status() !== 200) {
                throw_if($vendor_party_page->status() === 403, MarketPartyBlockedException::class);
                Log::notice('SnappFoodParty Error not 200: '.$vendor_party_page->status());

                return false;
            }

            $products = $vendor_party_page->json('data.superMarketMarketParty.data.products.list');

            if (empty($products)) {
                continue;
            }

            $new_product_hashes = collect();
            foreach ($products as $product) {
                $discount_price = $product['price'] - $product['discount'];
                $product_hash = md5($product['id'].$discount_price.$product['vendorCode']);

                if ($cache->has($product_hash)) {
                    continue;
                }

                if (in_array($product['title'], $marketParty->products) || $product['discountRatio'] >= $marketParty->threshold) {
                    $marketParty->notify(new MarketPartyNotification(product: $product, vendor: $vendor['data']));
                    $new_product_hashes->push($product_hash);
                }
            }

            if ($new_product_hashes->isNotEmpty()) {
                Redis::transaction(function (\Redis $redis) use ($marketParty, $new_product_hashes) {
                    $redis->hmset('mp_'.$marketParty->id,
                        $new_product_hashes->mapWithKeys(fn ($product_hash) => [$product_hash => 1])->toArray()
                    );
                    $redis->rawCommand('HEXPIRE', Redis::_prefix('mp_'.$marketParty->id), 60 * 60 * 12, 'NX', 'FIELDS', $new_product_hashes->count(), ...$new_product_hashes->toArray());
                });
            }
        }

        return true;
    }
}
