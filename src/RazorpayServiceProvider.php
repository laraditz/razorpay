<?php

namespace Laraditz\Razorpay;

use Illuminate\Support\ServiceProvider;
use Laraditz\Razorpay\Models\PaymentLink;
use Laraditz\Razorpay\Observers\PaymentLinkObserver;

class RazorpayServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'razorpay');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        PaymentLink::observe(PaymentLinkObserver::class);
    }
}
