<?php

namespace Laraditz\Razorpay\Services;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Models\RazorpaySettlement;

class SettlementService
{
    protected RazorpayClient $client;

    public function __construct(RazorpayClient $client)
    {
        $this->client = $client;
    }

    public function fetch(string $id): array
    {
        $response = $this->client->get("/settlements/{$id}");

        RazorpaySettlement::syncFromResponse($response);

        return $response;
    }

    public function all(array $query = []): array
    {
        $response = $this->client->get('/settlements', $query);

        foreach ($response['items'] ?? [] as $item) {
            RazorpaySettlement::syncFromResponse($item);
        }

        return $response;
    }
}
