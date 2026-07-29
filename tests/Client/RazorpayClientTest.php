<?php

namespace Laraditz\Razorpay\Tests\Client;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\Contracts\ClientInterface;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Tests\TestCase;

class RazorpayClientTest extends TestCase
{
    public function test_it_implements_client_interface(): void
    {
        $this->assertInstanceOf(ClientInterface::class, new RazorpayClient());
    }

    public function test_get_sends_query_and_returns_decoded_array(): void
    {
        Http::fake(['*' => Http::response(['count' => 1, 'items' => []], 200)]);

        $result = (new RazorpayClient())->get('/payment_links', ['count' => 10]);

        $this->assertSame(['count' => 1, 'items' => []], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links?count=10'
                && $request->method() === 'GET';
        });
    }

    public function test_post_sends_body_and_returns_decoded_array(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_123'], 200)]);

        $result = (new RazorpayClient())->post('/payment_links', ['amount' => 50000]);

        $this->assertSame(['id' => 'plink_123'], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links'
                && $request->method() === 'POST'
                && $request['amount'] === 50000;
        });
    }

    public function test_put_sends_body_and_returns_decoded_array(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_123'], 200)]);

        $result = (new RazorpayClient())->put('/payment_links/plink_123', ['notes' => ['a' => 'b']]);

        $this->assertSame(['id' => 'plink_123'], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links/plink_123'
                && $request->method() === 'PUT';
        });
    }

    public function test_patch_sends_body_and_returns_decoded_array(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_123'], 200)]);

        $result = (new RazorpayClient())->patch('/payment_links/plink_123', ['reference_id' => 'ref_1']);

        $this->assertSame(['id' => 'plink_123'], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links/plink_123'
                && $request->method() === 'PATCH'
                && $request['reference_id'] === 'ref_1';
        });
    }

    public function test_delete_returns_decoded_array(): void
    {
        Http::fake(['*' => Http::response(['deleted' => true], 200)]);

        $result = (new RazorpayClient())->delete('/payment_links/plink_123');

        $this->assertSame(['deleted' => true], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links/plink_123'
                && $request->method() === 'DELETE';
        });
    }
}
