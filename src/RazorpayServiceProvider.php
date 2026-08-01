<?php

namespace Laraditz\Razorpay;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Listeners\SyncOrderFromWebhook;
use Laraditz\Razorpay\Listeners\SyncPaymentLinkFromWebhook;
use Laraditz\Razorpay\Listeners\SyncRefundFromWebhook;
use Laraditz\Razorpay\Models\Order;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;
use Laraditz\Razorpay\Models\Refund;
use Laraditz\Razorpay\Observers\OrderObserver;
use Laraditz\Razorpay\Observers\PaymentLinkObserver;
use Laraditz\Razorpay\Observers\RefundObserver;

class RazorpayServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'razorpay');

        $this->app->singleton(RazorpayClient::class, function () {
            return new RazorpayClient();
        });

        $this->app->singleton('razorpay', function ($app) {
            return new Razorpay($app->make(RazorpayClient::class));
        });
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        RazorpayPaymentLink::observe(PaymentLinkObserver::class);
        Order::observe(OrderObserver::class);
        Refund::observe(RefundObserver::class);

        Event::listen(RazorpayWebhookReceived::class, SyncPaymentLinkFromWebhook::class);
        Event::listen(RazorpayWebhookReceived::class, SyncOrderFromWebhook::class);
        Event::listen(RazorpayWebhookReceived::class, SyncRefundFromWebhook::class);

        // Bare route, deliberately not wrapped in the `web` middleware
        // group, so Laravel's CSRF verification never sees this route.
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('razorpay.php'),
            ], 'razorpay-config');

            $this->publishMigrations();
        }
    }

    protected function publishMigrations(): void
    {
        $databasePath = __DIR__ . '/../database/migrations/';
        $migrationPath = database_path('migrations/');

        $files = array_diff(scandir($databasePath), ['.', '..']);
        $date = date('Y_m_d');
        $time = date('His');

        $migrationFiles = collect($files)
            ->mapWithKeys(function (string $file) use ($databasePath, $migrationPath, $date, &$time) {
                $filename = Str::replace(Str::substr($file, 0, 17), '', $file);

                $found = glob($migrationPath . '*' . $filename);
                $time = date('His', strtotime($time) + 1); // ensure in order

                return count($found) > 0 ? []
                    : [
                        $databasePath . $file => $migrationPath . $date . '_' . $time . $filename,
                    ];
            });

        if ($migrationFiles->isNotEmpty()) {
            $this->publishes($migrationFiles->toArray(), 'razorpay-migrations');
        }
    }
}
