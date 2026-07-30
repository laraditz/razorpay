<?php

namespace Laraditz\Razorpay\Tests\Observers;

use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Models\Order;
use Laraditz\Razorpay\Tests\TestCase;

class OrderObserverTest extends TestCase
{
    public function test_it_defaults_status_and_currency_when_not_set(): void
    {
        $order = Order::create([
            'razorpay_id' => 'order_defaults',
            'amount' => 1000,
        ]);

        $this->assertSame(OrderStatus::Created, $order->status);
        $this->assertSame(config('razorpay.default_currency'), $order->currency);
    }

    public function test_it_does_not_override_explicit_values(): void
    {
        $order = Order::create([
            'razorpay_id' => 'order_explicit',
            'amount' => 1000,
            'status' => OrderStatus::Paid,
            'currency' => 'USD',
        ]);

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('USD', $order->currency);
    }
}
