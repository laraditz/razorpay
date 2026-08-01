<?php

namespace Laraditz\Razorpay\Client\Concerns;

use Illuminate\Http\Client\Response;
use Laraditz\Razorpay\Models\RazorpayApiLog;

trait LogsApiCalls
{
    protected const REDACTED_CUSTOMER_FIELDS = ['name', 'email', 'contact'];

    protected function logApiCall(string $method, string $endpoint, array $data, ?Response $response, float $startedAt): void
    {
        if (!config('razorpay.log_api_calls', true)) {
            return;
        }

        $responsePayload = $response?->json();

        RazorpayApiLog::create([
            'method' => strtoupper($method),
            'endpoint' => $endpoint,
            'reference_id' => $responsePayload['id'] ?? null,
            'request_payload' => $this->redactPii($data),
            'response_payload' => is_null($responsePayload) ? null : $this->redactPii($responsePayload),
            'http_status' => $response?->status(),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);
    }

    protected function redactPii(array $data): array
    {
        if (isset($data['customer']) && is_array($data['customer'])) {
            $data['customer'] = $this->redactCustomerFields($data['customer']);
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = array_map(function ($item) {
                if (is_array($item) && isset($item['customer']) && is_array($item['customer'])) {
                    $item['customer'] = $this->redactCustomerFields($item['customer']);
                }

                return $item;
            }, $data['items']);
        }

        return $data;
    }

    protected function redactCustomerFields(array $customer): array
    {
        foreach (self::REDACTED_CUSTOMER_FIELDS as $field) {
            if (isset($customer[$field])) {
                $customer[$field] = '[redacted:'.hash_hmac('sha256', (string) $customer[$field], config('app.key')).']';
            }
        }

        return $customer;
    }
}
