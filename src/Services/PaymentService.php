<?php

namespace Laraditz\Razorpay\Services;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Models\RazorpayPayment;

class PaymentService
{
    protected RazorpayClient $client;

    public function __construct(RazorpayClient $client)
    {
        $this->client = $client;
    }

    public function fetch(string $id): array
    {
        $response = $this->client->get("/payments/{$id}");

        RazorpayPayment::syncFromResponse($response);

        return $response;
    }

    public function capture(string $id, array $data): array
    {
        $response = $this->client->post("/payments/{$id}/capture", $data);

        RazorpayPayment::syncFromResponse($response);

        return $response;
    }

    public function update(string $id, array $data): array
    {
        $response = $this->client->patch("/payments/{$id}", $data);

        RazorpayPayment::syncFromResponse($response);

        return $response;
    }
}
