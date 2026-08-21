<?php

return [
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-4-6'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    'orange_money' => [
        'api_key' => env('ORANGE_MONEY_API_KEY'),
        'api_secret' => env('ORANGE_MONEY_API_SECRET'),
        'merchant_id' => env('ORANGE_MONEY_MERCHANT_ID'),
        'webhook_secret' => env('ORANGE_MONEY_WEBHOOK_SECRET'),
        'base_url' => env('ORANGE_MONEY_BASE_URL'),
    ],

    'mpesa' => [
        'api_key' => env('MPESA_CONSUMER_KEY'),
        'api_secret' => env('MPESA_CONSUMER_SECRET'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'webhook_secret' => env('MPESA_WEBHOOK_SECRET'),
        'base_url' => env('MPESA_BASE_URL'),
    ],

    'airtel_money' => [
        'api_key' => env('AIRTEL_MONEY_CLIENT_ID'),
        'api_secret' => env('AIRTEL_MONEY_CLIENT_SECRET'),
        'webhook_secret' => env('AIRTEL_MONEY_WEBHOOK_SECRET'),
        'base_url' => env('AIRTEL_MONEY_BASE_URL'),
    ],

    'aggregator' => [
        'api_key' => env('AGGREGATOR_API_KEY'),
        'webhook_secret' => env('AGGREGATOR_WEBHOOK_SECRET'),
        'base_url' => env('AGGREGATOR_BASE_URL'),
    ],
];
