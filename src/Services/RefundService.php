<?php

namespace Laraditz\Razorpay\Services;

use Laraditz\Razorpay\Client\RazorpayClient;

class RefundService
{
    protected RazorpayClient $client;

    public function __construct(RazorpayClient $client)
    {
        $this->client = $client;
    }

    public function create(string $paymentId, array $data = []): array
    {
        return $this->client->post("/payments/{$paymentId}/refunds", $data);
    }
}
