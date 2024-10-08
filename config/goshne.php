<?php

return [
    'ttl' => [
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
        'food_party' => [
            'notify' => [
                'ttl' => env('FOOD_PARTY_NOTIFY_TTL', 12 * 60 * 60),
                'prefix' => 'fpp_',
            ],
        ],
    ],

    'scheduler' => [
        'food_party' => env('FOOD_PARTY_SCHEDULE', '*/15 * * * *'),
        'market_party' => env('MARKET_PARTY_SCHEDULE', '*/15 * * * *'),
    ],

    'default' => [
        'latitude' => env('DEFAULT_LATITUDE', 36.32112700482277),
        'longitude' => env('DEFAULT_LONGITUDE', 59.53740119934083),
        'image' => env('DEFAULT_IMAGE', 'https://raw.githubusercontent.com/ahbanavi/goshne/main/resource/default.jpg'),
    ],

];
