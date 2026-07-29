<?php

namespace Laraditz\Razorpay\Tests\Client\Concerns;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\Concerns\HandlesErrors;
use Laraditz\Razorpay\Tests\TestCase;

class HandlesErrorsTest extends TestCase
{
    protected function makeSubject()
    {
        return new class {
            use HandlesErrors;

            public function handle($response): array
            {
                return $this->handleResponse($response);
            }
        };
    }

    public function test_successful_response_returns_decoded_array(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_123', 'status' => 'created'], 200)]);

        $response = Http::get('https://api.razorpay.com/v1/payment_links');

        $result = $this->makeSubject()->handle($response);

        $this->assertSame(['id' => 'plink_123', 'status' => 'created'], $result);
    }
}
