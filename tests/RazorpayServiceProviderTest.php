<?php

namespace Laraditz\Razorpay\Tests;

use Laraditz\Razorpay\Client\RazorpayClient;

class RazorpayServiceProviderTest extends TestCase
{
    public function test_razorpay_client_resolves_as_a_singleton(): void
    {
        $first = $this->app->make(RazorpayClient::class);
        $second = $this->app->make(RazorpayClient::class);

        $this->assertInstanceOf(RazorpayClient::class, $first);
        $this->assertSame($first, $second);
    }
}
