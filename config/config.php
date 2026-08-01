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
    'default_currency' => env('RAZORPAY_CURRENCY', 'MYR'),

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

    /*
    |--------------------------------------------------------------------------
    | API Call Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, every outbound call RazorpayClient makes is recorded in
    | the razorpay_api_logs table (method, endpoint, request/response body,
    | status, duration). Customer PII in the payloads is redacted before
    | storage. Disable to skip logging entirely.
    |
    */
    'log_api_calls' => env('RAZORPAY_LOG_API_CALLS', true),

    /*
    |--------------------------------------------------------------------------
    | API Log Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep razorpay_api_logs rows. Rows older than this are
    | eligible for deletion via Laravel's built-in `php artisan model:prune`
    | command (ApiLog uses the Prunable trait) — no extra scheduling needed
    | beyond whatever your app already runs for model:prune.
    |
    */
    'api_log_retention_days' => env('RAZORPAY_API_LOG_RETENTION_DAYS', 30),
];
