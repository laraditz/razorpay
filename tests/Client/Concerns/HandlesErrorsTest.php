<?php

namespace Laraditz\Razorpay\Tests\Client\Concerns;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\Concerns\HandlesErrors;
use Laraditz\Razorpay\Exceptions\ApiException;
use Laraditz\Razorpay\Exceptions\AuthenticationException;
use Laraditz\Razorpay\Exceptions\ValidationException;
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

    public function test_401_response_throws_authentication_exception(): void
    {
        Http::fake(['*' => Http::response(['error' => ['description' => 'bad key']], 401)]);

        $response = Http::get('https://api.razorpay.com/v1/payment_links');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('bad key');

        $this->makeSubject()->handle($response);
    }

    public function test_400_response_throws_validation_exception_with_errors(): void
    {
        $body = ['error' => ['description' => 'amount is required', 'field' => 'amount']];
        Http::fake(['*' => Http::response($body, 400)]);

        $response = Http::get('https://api.razorpay.com/v1/payment_links');

        try {
            $this->makeSubject()->handle($response);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame('amount is required', $e->getMessage());
            $this->assertSame($body['error'], $e->getErrors());
        }
    }

    public function test_other_4xx_response_throws_api_exception_with_body(): void
    {
        $body = ['error' => ['description' => 'not found']];
        Http::fake(['*' => Http::response($body, 404)]);

        $response = Http::get('https://api.razorpay.com/v1/payment_links');

        try {
            $this->makeSubject()->handle($response);
            $this->fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            $this->assertSame('not found', $e->getMessage());
            $this->assertSame($body, $e->getResponse());
        }
    }

    public function test_5xx_response_throws_api_exception_with_body(): void
    {
        $body = ['error' => ['description' => 'internal error']];
        Http::fake(['*' => Http::response($body, 500)]);

        $response = Http::get('https://api.razorpay.com/v1/payment_links');

        try {
            $this->makeSubject()->handle($response);
            $this->fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            $this->assertSame($body, $e->getResponse());
        }
    }
}
