<?php

namespace Laraditz\Razorpay\Tests;

class PublishesAssetsTest extends TestCase
{
    protected function tearDown(): void
    {
        @unlink(config_path('razorpay.php'));

        // Generic glob — every package migration regardless of verb
        // (create_*, add_*_to_*, etc.), otherwise a leaked copy under a
        // published filename this glob doesn't match permanently pollutes
        // Testbench's skeleton app and causes "table/column already exists"
        // for any test that migrates afterward.
        foreach (glob(database_path('migrations/*razorpay*.php')) as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_config_is_publishable(): void
    {
        $this->artisan('vendor:publish', [
            '--provider' => \Laraditz\Razorpay\RazorpayServiceProvider::class,
            '--tag' => 'razorpay-config',
        ])->run();

        $this->assertFileExists(config_path('razorpay.php'));
    }

    public function test_migrations_are_publishable(): void
    {
        $this->artisan('vendor:publish', [
            '--provider' => \Laraditz\Razorpay\RazorpayServiceProvider::class,
            '--tag' => 'razorpay-migrations',
        ])->run();

        $published = glob(database_path('migrations/*_create_razorpay_payment_links_table.php'));

        $this->assertNotEmpty($published);
    }
}
