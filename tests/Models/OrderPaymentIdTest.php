<?php

namespace Laraditz\Razorpay\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Tests\TestCase;

class OrderPaymentIdTest extends TestCase
{
    public function test_razorpay_orders_table_has_payment_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('razorpay_orders', 'payment_id'));
    }

    public function test_payment_id_is_mass_assignable_and_persists(): void
    {
        $order = RazorpayOrder::create([
            'razorpay_id' => 'order_1',
            'amount' => 50000,
            'currency' => 'MYR',
            'status' => OrderStatus::Created,
            'payment_id' => 'pay_abc123',
        ]);

        $order->refresh();

        $this->assertSame('pay_abc123', $order->payment_id);
    }
}
