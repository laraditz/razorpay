<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Tests\TestCase;

class OrderTest extends TestCase
{
    public function test_it_casts_fields_correctly(): void
    {
        $order = RazorpayOrder::create([
            'razorpay_id' => 'order_test123',
            'status' => OrderStatus::Created,
            'amount' => 50000,
            'amount_paid' => 0,
            'amount_due' => 50000,
            'currency' => 'MYR',
            'attempts' => 0,
            'notes' => ['order' => 'ABC123'],
            'raw_response' => ['id' => 'order_test123'],
        ]);

        $order->refresh();

        $this->assertInstanceOf(OrderStatus::class, $order->status);
        $this->assertSame(OrderStatus::Created, $order->status);
        $this->assertIsInt($order->amount);
        $this->assertIsInt($order->amount_paid);
        $this->assertIsInt($order->amount_due);
        $this->assertIsInt($order->attempts);
        $this->assertIsArray($order->notes);
        $this->assertSame(['order' => 'ABC123'], $order->notes);
        $this->assertIsArray($order->raw_response);
    }

    public function test_it_is_soft_deletable(): void
    {
        $order = RazorpayOrder::create([
            'razorpay_id' => 'order_test456',
            'status' => OrderStatus::Created,
            'amount' => 1000,
            'currency' => 'MYR',
        ]);

        $order->delete();

        $this->assertSoftDeleted('razorpay_orders', ['id' => $order->id]);
    }
}
