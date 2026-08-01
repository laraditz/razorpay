<?php

namespace Laraditz\Razorpay\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentLinkPaymentIdTest extends TestCase
{
    public function test_razorpay_payment_links_table_has_payment_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('razorpay_payment_links', 'payment_id'));
    }

    public function test_payment_id_is_mass_assignable_and_persists(): void
    {
        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_1',
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => PaymentLinkStatus::Created,
            'payment_id' => 'pay_abc123',
        ]);

        $paymentLink->refresh();

        $this->assertSame('pay_abc123', $paymentLink->payment_id);
    }
}
