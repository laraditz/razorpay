<?php

namespace Laraditz\Razorpay\Tests;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Razorpay;
use Laraditz\Razorpay\Services\PaymentLinkService;
use Laraditz\Razorpay\Services\PaymentService;
use Laraditz\Razorpay\Services\SettlementService;

class RazorpayTest extends TestCase
{
    public function test_payment_link_returns_a_payment_link_service_wired_to_the_client(): void
    {
        $client = $this->app->make(RazorpayClient::class);
        $manager = new Razorpay($client);

        $this->assertInstanceOf(PaymentLinkService::class, $manager->paymentLink());
    }

    public function test_payment_returns_a_payment_service_wired_to_the_client(): void
    {
        $client = $this->app->make(RazorpayClient::class);
        $manager = new Razorpay($client);

        $this->assertInstanceOf(PaymentService::class, $manager->payment());
    }

    public function test_settlement_returns_a_settlement_service_wired_to_the_client(): void
    {
        $client = $this->app->make(RazorpayClient::class);
        $manager = new Razorpay($client);

        $this->assertInstanceOf(SettlementService::class, $manager->settlement());
    }
}
