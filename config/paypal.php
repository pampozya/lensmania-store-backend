<?php

return [
    'env' => env('PAYPAL_ENV', 'sandbox'),
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'secret' => env('PAYPAL_SECRET'),
    'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    'base_uri' => env('PAYPAL_BASE_URI', 'https://api-m.sandbox.paypal.com'),
    'live_base_uri' => 'https://api-m.paypal.com',
    'timeout' => 30,
];
