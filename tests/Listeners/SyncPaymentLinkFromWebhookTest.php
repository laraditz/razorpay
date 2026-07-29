<?php

namespace Laraditz\Razorpay\Tests\Listeners;

use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Listeners\SyncPaymentLinkFromWebhook;
use Laraditz\Razorpay\Models\PaymentLink;
use Laraditz\Razorpay\Tests\TestCase;

class SyncPaymentLinkFromWebhookTest extends TestCase
{
    protected function makePaymentLink(string $razorpayId, PaymentLinkStatus $status = PaymentLinkStatus::Created): PaymentLink
    {
        return PaymentLink::create([
            'razorpay_id' => $razorpayId,
            'amount' => 1000,
            'currency' => 'INR',
            'status' => $status,
        ]);
    }

    public function test_payment_link_paid_updates_status_and_paid_at(): void
    {
        $paymentLink = $this->makePaymentLink('plink_paid');

        $event = new RazorpayWebhookReceived('payment_link.paid', [
            'event' => 'payment_link.paid',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_paid']]],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $paymentLink->refresh();
        $this->assertSame(PaymentLinkStatus::Paid, $paymentLink->status);
        $this->assertNotNull($paymentLink->paid_at);
    }

    public function test_payment_link_cancelled_updates_status_and_cancelled_at(): void
    {
        $paymentLink = $this->makePaymentLink('plink_cancelled');

        $event = new RazorpayWebhookReceived('payment_link.cancelled', [
            'event' => 'payment_link.cancelled',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_cancelled']]],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $paymentLink->refresh();
        $this->assertSame(PaymentLinkStatus::Cancelled, $paymentLink->status);
        $this->assertNotNull($paymentLink->cancelled_at);
    }

    public function test_payment_link_expired_updates_status_and_expired_at(): void
    {
        $paymentLink = $this->makePaymentLink('plink_expired');

        $event = new RazorpayWebhookReceived('payment_link.expired', [
            'event' => 'payment_link.expired',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_expired']]],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $paymentLink->refresh();
        $this->assertSame(PaymentLinkStatus::Expired, $paymentLink->status);
        $this->assertNotNull($paymentLink->expired_at);
    }
}
