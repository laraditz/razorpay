<?php

namespace Laraditz\Razorpay\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laraditz\Razorpay\RazorpayServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [RazorpayServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('razorpay.key_id', 'test_key_id');
        $app['config']->set('razorpay.key_secret', 'test_key_secret');
        $app['config']->set('razorpay.webhook_secret', 'test_webhook_secret');
        $app['config']->set('razorpay.base_url', 'https://api.razorpay.com/v1');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
