<?php

use Illuminate\Support\Facades\Route;
use Laraditz\Razorpay\Http\Controllers\WebhookController;
use Laraditz\Razorpay\Http\Middleware\VerifyRazorpayWebhook;

Route::post(config('razorpay.webhook_path', '/razorpay/webhook'), [WebhookController::class, 'handle'])
    ->middleware(VerifyRazorpayWebhook::class)
    ->name('razorpay.webhook');
