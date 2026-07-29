<?php

namespace Laraditz\Razorpay\Tests;

class ConfigTest extends TestCase
{
    public function test_razorpay_config_is_registered(): void
    {
        $this->assertSame('test_key_id', config('razorpay.key_id'));
        $this->assertSame('test_key_secret', config('razorpay.key_secret'));
    }
}
