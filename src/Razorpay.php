<?php

namespace Laraditz\Razorpay;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Services\OrderService;
use Laraditz\Razorpay\Services\PaymentLinkService;

class Razorpay
{
    protected RazorpayClient $client;

    public function __construct(RazorpayClient $client)
    {
        $this->client = $client;
    }

    /**
     * Access Payment Link operations
     * https://razorpay.com/docs/api/payments/payment-links/
     */
    public function paymentLink(): PaymentLinkService
    {
        return new PaymentLinkService($this->client);
    }

    /**
     * Access Order operations
     * https://razorpay.com/docs/api/orders/
     */
    public function order(): OrderService
    {
        return new OrderService($this->client);
    }

    /**
     * Get the HTTP client instance
     */
    public function client(): RazorpayClient
    {
        return $this->client;
    }
}
