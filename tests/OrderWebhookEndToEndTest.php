<?php

namespace Laraditz\Razorpay\Tests;

use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Models\RazorpayOrder;

class OrderWebhookEndToEndTest extends TestCase
{
    public function test_valid_order_paid_webhook_updates_local_record_status(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $order = RazorpayOrder::create([
            'razorpay_id' => 'order_e2e',
            'amount' => 50000,
            'amount_paid' => 0,
            'amount_due' => 50000,
            'currency' => 'MYR',
            'status' => OrderStatus::Created,
        ]);

        $payload = [
            'event' => 'order.paid',
            'payload' => [
                'order' => [
                    'entity' => ['id' => 'order_e2e', 'amount_paid' => 50000, 'amount_due' => 0],
                ],
            ],
        ];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'whsec_test');

        $response = $this->call('POST', config('razorpay.webhook_path'), [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(50000, $order->amount_paid);
        $this->assertSame(0, $order->amount_due);
    }
}
