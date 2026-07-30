<?php

namespace Laraditz\Razorpay\Tests;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Razorpay;
use Laraditz\Razorpay\Services\OrderService;

class RazorpayOrderTest extends TestCase
{
    public function test_order_returns_an_order_service_wired_to_the_client(): void
    {
        $client = $this->app->make(RazorpayClient::class);
        $manager = new Razorpay($client);

        $this->assertInstanceOf(OrderService::class, $manager->order());
    }
}
