<?php

namespace Laraditz\Razorpay\Tests\Listeners;

use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Listeners\SyncPaymentLinkFromWebhook;
use Laraditz\Razorpay\Models\RazorpayOrder;
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
                'payment' => ['entity' => ['id' => 'pay_abc123', 'order_id' => 'order_xyz789']],
            ],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $paymentLink->refresh();
        $this->assertSame(PaymentLinkStatus::Paid, $paymentLink->status);
        $this->assertNotNull($paymentLink->paid_at);
        $this->assertSame('pay_abc123', $paymentLink->payment_id);
        $this->assertSame('order_xyz789', $paymentLink->order_id);
    }

    public function test_payment_link_paid_creates_local_order_from_embedded_entity(): void
    {
        $paymentLink = $this->makePaymentLink('plink_order_sync');

        $event = new RazorpayWebhookReceived('payment_link.paid', [
            'event' => 'payment_link.paid',
            'payload' => [
                'payment_link' => ['entity' => ['id' => 'plink_order_sync']],
                'payment' => ['entity' => ['id' => 'pay_abc123', 'order_id' => 'order_xyz789']],
                'order' => ['entity' => ['id' => 'order_xyz789', 'status' => 'paid', 'amount' => 1000, 'amount_paid' => 1000, 'amount_due' => 0, 'currency' => 'MYR']],
            ],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $order = RazorpayOrder::where('razorpay_id', 'order_xyz789')->first();
        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('pay_abc123', $order->payment_id);
    }

    public function test_payment_link_paid_syncs_order_even_when_no_matching_payment_link(): void
    {
        $event = new RazorpayWebhookReceived('payment_link.paid', [
            'event' => 'payment_link.paid',
            'payload' => [
                'payment_link' => ['entity' => ['id' => 'plink_does_not_exist']],
                'payment' => ['entity' => ['id' => 'pay_abc123', 'order_id' => 'order_untracked']],
                'order' => ['entity' => ['id' => 'order_untracked', 'status' => 'paid', 'amount' => 1000, 'amount_paid' => 1000, 'amount_due' => 0, 'currency' => 'MYR']],
            ],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $order = RazorpayOrder::where('razorpay_id', 'order_untracked')->first();
        $this->assertNotNull($order);
        $this->assertSame('pay_abc123', $order->payment_id);
    }

    public function test_payment_link_cancelled_does_not_touch_razorpay_orders(): void
    {
        $this->makePaymentLink('plink_cancelled_order_check');

        $event = new RazorpayWebhookReceived('payment_link.cancelled', [
            'event' => 'payment_link.cancelled',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_cancelled_order_check']]],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $this->assertSame(0, RazorpayOrder::count());
    }

    public function test_payment_link_paid_leaves_order_id_null_when_payment_entity_missing(): void
    {
        $paymentLink = $this->makePaymentLink('plink_paid_no_order');

        $event = new RazorpayWebhookReceived('payment_link.paid', [
            'event' => 'payment_link.paid',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_paid_no_order']]],
        ]);

        (new SyncPaymentLinkFromWebhook())->handle($event);

        $paymentLink->refresh();
        $this->assertSame(PaymentLinkStatus::Paid, $paymentLink->status);
        $this->assertNull($paymentLink->order_id);
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
                'payment' => ['entity' => ['id' => 'pay_abc123', 'order_id' => 'order_xyz789']],
            ],
        ]);

        $listener = new SyncPaymentLinkFromWebhook();

        \Illuminate\Support\Carbon::setTestNow('2026-01-01 00:00:00');
        $listener->handle($event);
        $paymentLink->refresh();
        $firstPaidAt = $paymentLink->paid_at;
        $firstPaymentId = $paymentLink->payment_id;
        $firstOrderId = $paymentLink->order_id;

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
        $this->assertSame($firstOrderId, $paymentLink->order_id);
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
