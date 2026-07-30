<?php

namespace Laraditz\Razorpay\Tests\Services;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Services\OrderService;
use Laraditz\Razorpay\Tests\TestCase;

class OrderServiceTest extends TestCase
{
    protected function makeService(): OrderService
    {
        return new OrderService(new RazorpayClient());
    }

    public function test_create_posts_to_orders_and_returns_array(): void
    {
        $responseBody = [
            'id' => 'order_EKwxwAgItmmXdp',
            'amount' => 50000,
            'amount_paid' => 0,
            'amount_due' => 50000,
            'currency' => 'MYR',
            'status' => 'created',
            'attempts' => 0,
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->create([
            'amount' => 50000,
            'currency' => 'MYR',
            'receipt' => 'receipt_1',
        ]);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/orders'
                && $request->method() === 'POST'
                && $request['amount'] === 50000
                && $request['receipt'] === 'receipt_1';
        });
    }
}
