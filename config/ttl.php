<?php

return [
    'market_party' => [
        'products' => [
            'ttl' => env('MARKET_PARTY_PRODUCTS_TTL', 15 * 60),
            'prefix' => 'mpvp_',
        ],
        'notify' => [
            'ttl' => env('MARKET_PARTY_NOTIFY_TTL', 12 * 60 * 60),
            'prefix' => 'mpp_',
        ],
    ],
];
