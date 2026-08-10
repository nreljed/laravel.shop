<?php

return [
    'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', '€'),

    'abandoned_cart' => [
        'enabled' => env('ABANDONED_CART_ENABLED', true),
        'hours_until_abandoned' => env('ABANDONED_CART_HOURS', 1),
        'sequence_hours' => [1, 24, 72],
    ],

    'hold_stock' => [
        'enabled' => env('HOLD_STOCK_ENABLED', true),
        'default_minutes' => (int) env('HOLD_STOCK_MINUTES', 60),
    ],

    'inventory' => [
        'default_low_stock_threshold' => (int) env('DEFAULT_LOW_STOCK_THRESHOLD', 5),
        'enabled' => true,
    ],

    'sale_scheduling' => [
        'enabled' => env('SALE_SCHEDULING_ENABLED', true),
        'normalize_to_day_boundaries' => true,
    ],

    'storefront' => [
        'hero' => [
            'title' => env('STORE_HERO_TITLE', 'Welcome to our store'),
            'subtitle' => env('STORE_HERO_SUBTITLE', 'Discover quality products hand-picked for you. Free shipping on orders over 50 EUR.'),
            'cta_label' => env('STORE_HERO_CTA', 'Shop now'),
        ],
    ],
];
