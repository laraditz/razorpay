<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Razorpay Key ID
    |--------------------------------------------------------------------------
    |
    | Your Razorpay API Key ID. Get it from Razorpay Dashboard.
    | https://dashboard.razorpay.com/app/keys
    |
    */
    'key_id' => env('RAZORPAY_KEY_ID'),

    /*
    |--------------------------------------------------------------------------
    | Razorpay Key Secret
    |--------------------------------------------------------------------------
    |
    | Your Razorpay API Key Secret. Get it from Razorpay Dashboard.
    |
    */
    'key_secret' => env('RAZORPAY_KEY_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Razorpay Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for Razorpay's REST API.
    |
    */
    'base_url' => env('RAZORPAY_BASE_URL', 'https://api.razorpay.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency to use when creating a Payment Link, if not
    | explicitly provided in the request.
    |
    */
    'default_currency' => env('RAZORPAY_CURRENCY', 'INR'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | HTTP request timeout in seconds.
    |
    */
    'timeout' => env('RAZORPAY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The webhook secret configured in your Razorpay Dashboard, used to verify
    | the X-Razorpay-Signature header on incoming webhook requests.
    |
    */
    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Path
    |--------------------------------------------------------------------------
    |
    | The path this package registers to receive Razorpay webhook requests.
    |
    */
    'webhook_path' => env('RAZORPAY_WEBHOOK_PATH', '/razorpay/webhook'),
];
