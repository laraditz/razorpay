<?php

namespace Laraditz\Razorpay\Tests\Observers;

use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentLinkObserverTest extends TestCase
{
    public function test_it_defaults_status_and_currency_when_not_set(): void
    {
        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_defaults',
            'amount' => 1000,
        ]);

        $this->assertSame(PaymentLinkStatus::Created, $paymentLink->status);
        $this->assertSame(config('razorpay.default_currency'), $paymentLink->currency);
    }

    public function test_it_does_not_override_explicit_values(): void
    {
        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_explicit',
            'amount' => 1000,
            'status' => PaymentLinkStatus::Paid,
            'currency' => 'MYR',
        ]);

        $this->assertSame(PaymentLinkStatus::Paid, $paymentLink->status);
        $this->assertSame('MYR', $paymentLink->currency);
    }
}
