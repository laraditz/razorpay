<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Enums\PaymentStatus;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Models\RazorpayPayment;
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

    public function test_payment_relationship_resolves_via_payment_id(): void
    {
        $payment = RazorpayPayment::create([
            'razorpay_id' => 'pay_1',
            'status' => PaymentStatus::Captured,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_test789',
            'payment_id' => 'pay_1',
            'status' => PaymentLinkStatus::Paid,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $this->assertTrue($paymentLink->payment->is($payment));
    }

    public function test_payment_relationship_is_null_when_no_match(): void
    {
        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_test999',
            'status' => PaymentLinkStatus::Created,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $this->assertNull($paymentLink->payment);
    }

    public function test_order_relationship_resolves_via_order_id(): void
    {
        $order = RazorpayOrder::create([
            'razorpay_id' => 'order_test_plink',
            'status' => OrderStatus::Paid,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_order_test',
            'order_id' => 'order_test_plink',
            'status' => PaymentLinkStatus::Paid,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $this->assertTrue($paymentLink->order->is($order));
    }

    public function test_order_relationship_is_null_when_no_match(): void
    {
        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_order_no_match',
            'status' => PaymentLinkStatus::Created,
            'amount' => 50000,
            'currency' => 'MYR',
        ]);

        $this->assertNull($paymentLink->order);
    }
}
