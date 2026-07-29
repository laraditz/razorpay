<?php

namespace Laraditz\Razorpay;

use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Laraditz\Razorpay\Client\RazorpayClient;
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

        $this->app->singleton(RazorpayClient::class, function () {
            return new RazorpayClient();
        });
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        PaymentLink::observe(PaymentLinkObserver::class);

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
