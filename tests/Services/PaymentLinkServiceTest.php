<?php

namespace Laraditz\Razorpay\Tests\Services;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Services\PaymentLinkService;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentLinkServiceTest extends TestCase
{
    protected function makeService(): PaymentLinkService
    {
        return new PaymentLinkService(new RazorpayClient());
    }

    public function test_create_posts_to_payment_links_and_returns_array(): void
    {
        $responseBody = [
            'id' => 'plink_ExjpAUN3gVHrPJ',
            'order_id' => 'order_ExjpAUN3gVHrPK',
            'amount' => 50000,
            'currency' => 'INR',
            'status' => 'created',
            'short_url' => 'https://rzp.io/i/abc123',
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->create([
            'amount' => 50000,
            'currency' => 'INR',
            'description' => 'Test payment',
        ]);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links'
                && $request->method() === 'POST'
                && $request['amount'] === 50000
                && $request['description'] === 'Test payment';
        });
    }
}
