<?php

namespace Laraditz\Razorpay\Client\Concerns;

use Illuminate\Http\Client\Response;
use Laraditz\Razorpay\Exceptions\ApiException;
use Laraditz\Razorpay\Exceptions\AuthenticationException;
use Laraditz\Razorpay\Exceptions\ValidationException;

trait HandlesErrors
{
    protected function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $this->throwException($response);
    }

    protected function throwException(Response $response): void
    {
        $statusCode = $response->status();
        $body = $response->json() ?? [];
        $message = $body['error']['description'] ?? 'An error occurred';

        match (true) {
            $statusCode === 401 => throw new AuthenticationException($message, $statusCode),
            $statusCode === 400 => throw new ValidationException($message, $body['error'] ?? [], $statusCode),
            $statusCode >= 400 && $statusCode < 500 => throw new ApiException($message, $statusCode, $body),
            $statusCode >= 500 => throw new ApiException('Razorpay server error: ' . $message, $statusCode, $body),
            default => throw new ApiException($message, $statusCode, $body),
        };
    }
}
