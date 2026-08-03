<?php

namespace Laraditz\Razorpay\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Models\RazorpayRefund;
use Laraditz\Razorpay\Tests\TestCase;

class OrderSubjectTest extends TestCase
{
    public function test_razorpay_orders_table_has_subject_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('razorpay_orders', 'subject_id'));
        $this->assertTrue(Schema::hasColumn('razorpay_orders', 'subject_type'));
    }

    public function test_subject_relationship_resolves_the_attached_model(): void
    {
        // RazorpayRefund is a convenient stand-in "arbitrary model" here —
        // it's just any Eloquent model with a real table, unrelated to Order
        // in any other way.
        $subject = RazorpayRefund::create([
            'razorpay_id' => 'rfnd_subject_test',
            'payment_id' => 'pay_1',
            'status' => \Laraditz\Razorpay\Enums\RefundStatus::Pending,
            'amount' => 1000,
            'currency' => 'MYR',
        ]);

        $order = RazorpayOrder::create([
            'razorpay_id' => 'order_subject_test',
            'status' => OrderStatus::Created,
            'amount' => 50000,
            'currency' => 'MYR',
            'subject_id' => $subject->id,
            'subject_type' => $subject->getMorphClass(),
        ]);

        $this->assertTrue($order->subject->is($subject));
    }

    public function test_subject_is_null_when_not_set(): void
    {
        $order = RazorpayOrder::create([
            'razorpay_id' => 'order_no_subject',
            'status' => OrderStatus::Created,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $this->assertNull($order->subject);
    }
}
