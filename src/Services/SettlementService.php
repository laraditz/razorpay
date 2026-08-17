<?php

namespace Laraditz\Razorpay\Services;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Models\RazorpaySettlement;
use Laraditz\Razorpay\Models\RazorpaySettlementTransaction;

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

    public function fetchRecon(int $year, int $month, ?int $day = null, ?int $count = null, ?int $skip = null): array
    {
        $response = $this->client->get('/settlements/recon/combined', array_filter([
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'count' => $count,
            'skip' => $skip,
        ], fn ($value) => $value !== null));

        foreach ($response['items'] ?? [] as $item) {
            RazorpaySettlementTransaction::syncFromResponse($item);
        }

        return $response;
    }
}
