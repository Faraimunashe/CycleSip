<?php

return [
    'mode' => env('ECOCASH_MODE', 'sandbox'),
    'api_key' => env('ECOCASH_API_KEY'),
    'base_url' => rtrim(env('ECOCASH_BASE_URL', 'https://developers.ecocash.co.zw/api/ecocash_pay'), '/'),
    'currency' => env('ECOCASH_CURRENCY', 'USD'),
    'endpoints' => [
        'sandbox' => '/api/v2/payment/instant/c2b/sandbox',
        'live' => '/api/v2/payment/instant/c2b',
    ],
];
