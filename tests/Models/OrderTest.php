<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Enums\PaymentStatus;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Models\RazorpayPayment;
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

    public function test_payment_relationship_resolves_via_payment_id(): void
    {
        $payment = RazorpayPayment::create([
            'razorpay_id' => 'pay_1',
            'status' => PaymentStatus::Captured,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $order = RazorpayOrder::create([
            'razorpay_id' => 'order_test789',
            'payment_id' => 'pay_1',
            'status' => OrderStatus::Paid,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $this->assertTrue($order->payment->is($payment));
    }

    public function test_payment_relationship_is_null_when_no_match(): void
    {
        $order = RazorpayOrder::create([
            'razorpay_id' => 'order_test999',
            'status' => OrderStatus::Created,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $this->assertNull($order->payment);
    }
}
