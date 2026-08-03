<?php

namespace Laraditz\Razorpay\Tests\Models;

use Illuminate\Support\Facades\Schema;
use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;
use Laraditz\Razorpay\Models\RazorpayRefund;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentLinkSubjectTest extends TestCase
{
    public function test_razorpay_payment_links_table_has_subject_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('razorpay_payment_links', 'subject_id'));
        $this->assertTrue(Schema::hasColumn('razorpay_payment_links', 'subject_type'));
    }

    public function test_subject_relationship_resolves_the_attached_model(): void
    {
        $subject = RazorpayRefund::create([
            'razorpay_id' => 'rfnd_plink_subject_test',
            'payment_id' => 'pay_1',
            'status' => RefundStatus::Pending,
            'amount' => 1000,
            'currency' => 'MYR',
        ]);

        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_subject_test',
            'status' => PaymentLinkStatus::Created,
            'amount' => 50000,
            'currency' => 'MYR',
            'subject_id' => $subject->id,
            'subject_type' => $subject->getMorphClass(),
        ]);

        $this->assertTrue($paymentLink->subject->is($subject));
    }

    public function test_subject_is_null_when_not_set(): void
    {
        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_no_subject',
            'status' => PaymentLinkStatus::Created,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $this->assertNull($paymentLink->subject);
    }
}
