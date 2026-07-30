<?php

namespace Laraditz\Razorpay\Tests\Services;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Models\Order;
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

    public function test_create_persists_a_local_order_record(): void
    {
        $responseBody = [
            'id' => 'order_EKwxwAgItmmXdp',
            'amount' => 50000,
            'amount_paid' => 0,
            'amount_due' => 50000,
            'currency' => 'MYR',
            'status' => 'created',
            'receipt' => 'receipt_1',
            'attempts' => 0,
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $this->makeService()->create(['amount' => 50000, 'currency' => 'MYR']);

        $order = Order::where('razorpay_id', 'order_EKwxwAgItmmXdp')->first();

        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::Created, $order->status);
        $this->assertSame(50000, $order->amount);
        $this->assertSame(0, $order->amount_paid);
        $this->assertSame(50000, $order->amount_due);
        $this->assertSame('MYR', $order->currency);
        $this->assertSame('receipt_1', $order->receipt);
        $this->assertSame(0, $order->attempts);
        $this->assertSame($responseBody, $order->raw_response);
    }

    public function test_fetch_gets_order_and_does_not_touch_local_record(): void
    {
        $responseBody = ['id' => 'order_EKwxwAgItmmXdp', 'status' => 'paid'];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->fetch('order_EKwxwAgItmmXdp');

        $this->assertSame($responseBody, $result);
        $this->assertSame(0, Order::count());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/orders/order_EKwxwAgItmmXdp'
                && $request->method() === 'GET';
        });
    }

    public function test_all_forwards_query_params_and_returns_list_envelope(): void
    {
        $responseBody = ['entity' => 'collection', 'count' => 1, 'items' => [['id' => 'order_1']]];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->all(['count' => 5, 'skip' => 0, 'receipt' => 'receipt_1']);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.razorpay.com/v1/orders?')
                && $request->method() === 'GET'
                && $request['count'] == 5
                && $request['receipt'] === 'receipt_1';
        });
    }
}
