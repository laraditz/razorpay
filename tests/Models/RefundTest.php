<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\RefundStatus;
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
}
