<?php

namespace Laraditz\Razorpay\Tests\Observers;

use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Models\RazorpayRefund;
use Laraditz\Razorpay\Tests\TestCase;

class RefundObserverTest extends TestCase
{
    public function test_it_defaults_status_and_currency_when_not_set(): void
    {
        $refund = RazorpayRefund::create([
            'razorpay_id' => 'rfnd_defaults',
            'payment_id' => 'pay_defaults',
            'amount' => 1000,
        ]);

        $this->assertSame(RefundStatus::Pending, $refund->status);
        $this->assertSame(config('razorpay.default_currency'), $refund->currency);
    }

    public function test_it_does_not_override_explicit_values(): void
    {
        $refund = RazorpayRefund::create([
            'razorpay_id' => 'rfnd_explicit',
            'payment_id' => 'pay_explicit',
            'amount' => 1000,
            'status' => RefundStatus::Processed,
            'currency' => 'USD',
        ]);

        $this->assertSame(RefundStatus::Processed, $refund->status);
        $this->assertSame('USD', $refund->currency);
    }
}
