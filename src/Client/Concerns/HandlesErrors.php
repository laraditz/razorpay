<?php

namespace Laraditz\Razorpay\Client\Concerns;

use Illuminate\Http\Client\Response;

trait HandlesErrors
{
    protected function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $this->throwException($response);
    }
}
