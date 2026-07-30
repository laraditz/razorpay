<?php

namespace Laraditz\Razorpay\Tests;

class ConfigDefaultCurrencyTest extends TestCase
{
    public function test_default_currency_is_myr(): void
    {
        $raw = require __DIR__ . '/../config/config.php';

        $this->assertSame('MYR', $raw['default_currency']);
    }
}
