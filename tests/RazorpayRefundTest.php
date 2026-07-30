<?php

namespace Laraditz\Razorpay\Tests;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Razorpay;
use Laraditz\Razorpay\Services\RefundService;

class RazorpayRefundTest extends TestCase
{
    public function test_refund_returns_a_refund_service_wired_to_the_client(): void
    {
        $client = $this->app->make(RazorpayClient::class);
        $manager = new Razorpay($client);

        $this->assertInstanceOf(RefundService::class, $manager->refund());
    }
}
