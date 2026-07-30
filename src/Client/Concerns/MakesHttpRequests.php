<?php

namespace Laraditz\Razorpay\Client\Concerns;

use Illuminate\Support\Facades\Http;

trait MakesHttpRequests
{
    protected function buildClient()
    {
        return Http::withHeaders(array_merge(
            $this->getAuthHeaders(),
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ))
            ->baseUrl($this->getBaseUrl())
            ->timeout(config('razorpay.timeout', 30));
    }

    protected function getBaseUrl(): string
    {
        return config('razorpay.base_url', 'https://api.razorpay.com/v1');
    }
}
