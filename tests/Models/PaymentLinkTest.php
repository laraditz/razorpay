<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentLinkTest extends TestCase
{
    public function test_it_casts_fields_correctly(): void
    {
        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_test123',
            'order_id' => 'order_test123',
            'status' => PaymentLinkStatus::Created,
            'amount' => 50000,
            'currency' => 'MYR',
            'notify_sms' => true,
            'notify_email' => 1,
            'notes' => ['order' => 'ABC123'],
            'raw_response' => ['id' => 'plink_test123'],
            'expire_by' => now()->addDay(),
        ]);

        $paymentLink->refresh();

        $this->assertInstanceOf(PaymentLinkStatus::class, $paymentLink->status);
        $this->assertSame(PaymentLinkStatus::Created, $paymentLink->status);
        $this->assertIsInt($paymentLink->amount);
        $this->assertTrue($paymentLink->notify_sms);
        $this->assertTrue($paymentLink->notify_email);
        $this->assertIsArray($paymentLink->notes);
        $this->assertSame(['order' => 'ABC123'], $paymentLink->notes);
        $this->assertIsArray($paymentLink->raw_response);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $paymentLink->expire_by);
    }

    public function test_it_is_soft_deletable(): void
    {
        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_test456',
            'status' => PaymentLinkStatus::Created,
            'amount' => 1000,
            'currency' => 'MYR',
        ]);

        $paymentLink->delete();

        $this->assertSoftDeleted('razorpay_payment_links', ['id' => $paymentLink->id]);
    }
}
