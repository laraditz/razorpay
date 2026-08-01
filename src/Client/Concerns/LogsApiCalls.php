<?php

namespace Laraditz\Razorpay\Client\Concerns;

use Illuminate\Http\Client\Response;
use Laraditz\Razorpay\Models\ApiLog;

trait LogsApiCalls
{
    protected function logApiCall(string $method, string $endpoint, array $data, ?Response $response, float $startedAt): void
    {
        ApiLog::create([
            'method' => strtoupper($method),
            'endpoint' => $endpoint,
            'request_payload' => $data,
            'response_payload' => $response?->json(),
            'http_status' => $response?->status(),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);
    }
}
