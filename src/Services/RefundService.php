<?php

namespace Laraditz\Razorpay\Services;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Models\Refund;

class RefundService
{
    protected RazorpayClient $client;

    public function __construct(RazorpayClient $client)
    {
        $this->client = $client;
    }

    public function create(string $paymentId, array $data = []): array
    {
        $response = $this->client->post("/payments/{$paymentId}/refunds", $data);

        $this->storeLocalRecord($response);

        return $response;
    }

    public function fetch(string $id): array
    {
        return $this->client->get("/refunds/{$id}");
    }

    public function all(array $query = []): array
    {
        return $this->client->get('/refunds', $query);
    }

    public function forPayment(string $paymentId, array $query = []): array
    {
        return $this->client->get("/payments/{$paymentId}/refunds", $query);
    }

    protected function storeLocalRecord(array $response): Refund
    {
        return Refund::create([
            'razorpay_id' => $response['id'] ?? null,
            'payment_id' => $response['payment_id'] ?? null,
            'status' => $response['status'] ?? null,
            'amount' => $response['amount'] ?? null,
            'currency' => $response['currency'] ?? null,
            'notes' => $response['notes'] ?? null,
            'receipt' => $response['receipt'] ?? null,
            'speed_requested' => $response['speed_requested'] ?? null,
            'speed_processed' => $response['speed_processed'] ?? null,
            'raw_response' => $response,
        ]);
    }
}
