<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\PaymentStatus;
use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Models\RazorpayPayment;
use Laraditz\Razorpay\Models\RazorpayRefund;
use Laraditz\Razorpay\Tests\TestCase;

class RefundTest extends TestCase
{
    public function test_it_casts_fields_correctly(): void
    {
        $refund = RazorpayRefund::create([
            'razorpay_id' => 'rfnd_test123',
            'payment_id' => 'pay_test123',
            'status' => RefundStatus::Pending,
            'amount' => 10000,
            'currency' => 'MYR',
            'notes' => ['reason' => 'requested by customer'],
            'raw_response' => ['id' => 'rfnd_test123'],
        ]);

        $refund->refresh();

        $this->assertInstanceOf(RefundStatus::class, $refund->status);
        $this->assertSame(RefundStatus::Pending, $refund->status);
        $this->assertIsInt($refund->amount);
        $this->assertIsArray($refund->notes);
        $this->assertSame(['reason' => 'requested by customer'], $refund->notes);
        $this->assertIsArray($refund->raw_response);
        $this->assertSame('pay_test123', $refund->payment_id);
    }

    public function test_it_is_soft_deletable(): void
    {
        $refund = RazorpayRefund::create([
            'razorpay_id' => 'rfnd_test456',
            'payment_id' => 'pay_test456',
            'status' => RefundStatus::Pending,
            'amount' => 1000,
            'currency' => 'MYR',
        ]);

        $refund->delete();

        $this->assertSoftDeleted('razorpay_refunds', ['id' => $refund->id]);
    }

    public function test_payment_relationship_resolves_via_payment_id(): void
    {
        $payment = RazorpayPayment::create([
            'razorpay_id' => 'pay_test789',
            'status' => PaymentStatus::Captured,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $refund = RazorpayRefund::create([
            'razorpay_id' => 'rfnd_test789',
            'payment_id' => 'pay_test789',
            'status' => RefundStatus::Pending,
            'amount' => 10000,
            'currency' => 'MYR',
        ]);

        $this->assertTrue($refund->payment->is($payment));
    }

    public function test_payment_relationship_is_null_when_no_match(): void
    {
        $refund = RazorpayRefund::create([
            'razorpay_id' => 'rfnd_test999',
            'payment_id' => 'pay_does_not_exist',
            'status' => RefundStatus::Pending,
            'amount' => 10000,
            'currency' => 'MYR',
        ]);

        $this->assertNull($refund->payment);
    }
}
