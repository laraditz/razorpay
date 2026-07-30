<?php

namespace Laraditz\Razorpay\Tests;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Razorpay;
use Laraditz\Razorpay\Services\PaymentLinkService;

class RazorpayTest extends TestCase
{
    public function test_payment_link_returns_a_payment_link_service_wired_to_the_client(): void
    {
        $client = $this->app->make(RazorpayClient::class);
        $manager = new Razorpay($client);

        $this->assertInstanceOf(PaymentLinkService::class, $manager->paymentLink());
    }
}
