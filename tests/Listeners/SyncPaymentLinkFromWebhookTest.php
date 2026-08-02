<?php

namespace Laraditz\Razorpay\Tests\Listeners;

use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Listeners\SyncPaymentLinkFromWebhook;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;
use Laraditz\Razorpay\Tests\TestCase;

class SyncPaymentLinkFromWebhookTest extends TestCase
{
    protected function makePaymentLink(string $razorpayId, PaymentLinkStatus $status = PaymentLinkStatus::Created): RazorpayPaymentLink
    {
        return RazorpayPaymentLink::create([
            'razorpay_id' => $razorpayId,
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => $status,
        ]);
    }

    public function test_payment_link_paid_updates_status_and_paid_at(): void
    {
        $paymentLink = $this->makePaymentLink('plink_paid');

        $event = new RazorpayWebhookReceived('payment_link.paid', [
            'event' => 'payment_link.paid',
            'payload' => [
                'payment_link' => ['entity' => ['id' => 'plink_paid']],
                'payment' => ['entity' => ['id' => 'pay_abc123']],
            ],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $paymentLink->refresh();
        $this->assertSame(PaymentLinkStatus::Paid, $paymentLink->status);
        $this->assertNotNull($paymentLink->paid_at);
        $this->assertSame('pay_abc123', $paymentLink->payment_id);
    }

    public function test_payment_link_paid_leaves_payment_id_null_when_payment_entity_missing(): void
    {
        $paymentLink = $this->makePaymentLink('plink_paid_no_payment');

        $event = new RazorpayWebhookReceived('payment_link.paid', [
            'event' => 'payment_link.paid',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_paid_no_payment']]],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $paymentLink->refresh();
        $this->assertSame(PaymentLinkStatus::Paid, $paymentLink->status);
        $this->assertNull($paymentLink->payment_id);
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
        $this->assertNull($paymentLink->payment_id);
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
        $this->assertNull($paymentLink->payment_id);
    }

    public function test_redelivering_the_same_event_is_idempotent(): void
    {
        $paymentLink = $this->makePaymentLink('plink_idempotent');

        $event = new RazorpayWebhookReceived('payment_link.paid', [
            'event' => 'payment_link.paid',
            'payload' => [
                'payment_link' => ['entity' => ['id' => 'plink_idempotent']],
                'payment' => ['entity' => ['id' => 'pay_abc123']],
            ],
        ]);

        $listener = new SyncPaymentLinkFromWebhook();

        \Illuminate\Support\Carbon::setTestNow('2026-01-01 00:00:00');
        $listener->handle($event);
        $paymentLink->refresh();
        $firstPaidAt = $paymentLink->paid_at;
        $firstPaymentId = $paymentLink->payment_id;

        // Advance the clock — if the listener isn't truly idempotent (i.e. it
        // blindly re-applies now() on every delivery instead of skipping an
        // already-synced status), paid_at would drift to this later time.
        \Illuminate\Support\Carbon::setTestNow('2026-01-01 00:05:00');
        $listener->handle($event);
        $paymentLink->refresh();

        \Illuminate\Support\Carbon::setTestNow();

        $this->assertSame(PaymentLinkStatus::Paid, $paymentLink->status);
        $this->assertNotNull($paymentLink->paid_at);
        $this->assertSame($firstPaidAt->toIso8601String(), $paymentLink->paid_at->toIso8601String());
        $this->assertSame($firstPaymentId, $paymentLink->payment_id);
    }

    public function test_no_matching_local_record_does_not_throw(): void
    {
        $event = new RazorpayWebhookReceived('payment_link.paid', [
            'event' => 'payment_link.paid',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_does_not_exist']]],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $this->assertTrue(true); // reaching here means no exception was thrown
    }
}
