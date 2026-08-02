<?php

namespace Laraditz\Razorpay;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Services\OrderService;
use Laraditz\Razorpay\Services\PaymentLinkService;
use Laraditz\Razorpay\Services\PaymentService;
use Laraditz\Razorpay\Services\RefundService;
use Laraditz\Razorpay\Services\SettlementService;

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
     * Access Refund operations
     * https://razorpay.com/docs/api/refunds/
     */
    public function refund(): RefundService
    {
        return new RefundService($this->client);
    }

    /**
     * Access Payment operations
     * https://razorpay.com/docs/api/payments/
     */
    public function payment(): PaymentService
    {
        return new PaymentService($this->client);
    }

    /**
     * Access Settlement operations
     * https://razorpay.com/docs/api/settlements/
     */
    public function settlement(): SettlementService
    {
        return new SettlementService($this->client);
    }

    /**
     * Get the HTTP client instance
     */
    public function client(): RazorpayClient
    {
        return $this->client;
    }
}
