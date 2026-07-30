<?php

namespace Laraditz\Razorpay\Services;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Models\Order;

class OrderService
{
    protected RazorpayClient $client;

    public function __construct(RazorpayClient $client)
    {
        $this->client = $client;
    }

    public function create(array $data): array
    {
        $response = $this->client->post('/orders', $data);

        $this->storeLocalRecord($response);

        return $response;
    }

    public function fetch(string $id): array
    {
        return $this->client->get("/orders/{$id}");
    }

    public function all(array $query = []): array
    {
        return $this->client->get('/orders', $query);
    }

    protected function storeLocalRecord(array $response): Order
    {
        return Order::create([
            'razorpay_id' => $response['id'] ?? null,
            'status' => $response['status'] ?? null,
            'amount' => $response['amount'] ?? null,
            'amount_paid' => $response['amount_paid'] ?? null,
            'amount_due' => $response['amount_due'] ?? null,
            'currency' => $response['currency'] ?? null,
            'receipt' => $response['receipt'] ?? null,
            'attempts' => $response['attempts'] ?? 0,
            'notes' => $response['notes'] ?? null,
            'raw_response' => $response,
        ]);
    }
}
