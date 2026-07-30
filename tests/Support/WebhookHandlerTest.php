<?php

namespace Laraditz\Razorpay\Tests\Support;

use Illuminate\Support\Facades\Event;
use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Events\OrderPaid;
use Laraditz\Razorpay\Events\PaymentCaptured;
use Laraditz\Razorpay\Events\PaymentFailed;
use Laraditz\Razorpay\Events\PaymentLinkPaid;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Events\RefundCreated;
use Laraditz\Razorpay\Events\RefundFailed;
use Laraditz\Razorpay\Events\RefundProcessed;
use Laraditz\Razorpay\Models\Order;
use Laraditz\Razorpay\Models\PaymentLink;
use Laraditz\Razorpay\Models\Refund;
use Laraditz\Razorpay\Support\WebhookHandler;
use Laraditz\Razorpay\Tests\TestCase;

class WebhookHandlerTest extends TestCase
{
    public function test_known_event_type_dispatches_generic_event(): void
    {
        Event::fake();

        $payload = ['event' => 'payment_link.paid', 'payload' => []];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(RazorpayWebhookReceived::class, function ($event) use ($payload) {
            return $event->eventType === 'payment_link.paid' && $event->payload === $payload;
        });
    }

    public function test_unknown_event_type_still_dispatches_generic_event_only(): void
    {
        Event::fake();

        $payload = ['event' => 'some.future.event', 'payload' => []];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(RazorpayWebhookReceived::class, function ($event) use ($payload) {
            return $event->eventType === 'some.future.event' && $event->payload === $payload;
        });
        Event::assertNotDispatched(PaymentLinkPaid::class);
        Event::assertNotDispatched(PaymentCaptured::class);
        Event::assertNotDispatched(PaymentFailed::class);
    }

    public function test_payment_link_paid_dispatches_with_matching_local_record(): void
    {
        Event::fake();

        $paymentLink = PaymentLink::create([
            'razorpay_id' => 'plink_1',
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => \Laraditz\Razorpay\Enums\PaymentLinkStatus::Created,
        ]);

        $payload = [
            'event' => 'payment_link.paid',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_1']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentLinkPaid::class, function ($event) use ($paymentLink, $payload) {
            return $event->paymentLink->is($paymentLink) && $event->payload === $payload;
        });
    }

    public function test_payment_link_paid_dispatches_with_null_model_when_no_match(): void
    {
        Event::fake();

        $payload = [
            'event' => 'payment_link.paid',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_does_not_exist']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentLinkPaid::class, function ($event) use ($payload) {
            return $event->paymentLink === null && $event->payload === $payload;
        });
    }

    public function test_payment_captured_dispatches_with_matching_local_record_via_order_id(): void
    {
        Event::fake();

        $paymentLink = PaymentLink::create([
            'razorpay_id' => 'plink_1',
            'order_id' => 'order_1',
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => \Laraditz\Razorpay\Enums\PaymentLinkStatus::Created,
        ]);

        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_1', 'order_id' => 'order_1']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentCaptured::class, function ($event) use ($paymentLink, $payload) {
            return $event->paymentLink->is($paymentLink) && $event->payload === $payload;
        });
    }

    public function test_payment_captured_dispatches_with_null_model_when_order_id_missing(): void
    {
        Event::fake();

        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_1']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentCaptured::class, function ($event) use ($payload) {
            return $event->paymentLink === null && $event->payload === $payload;
        });
    }

    public function test_payment_failed_dispatches_with_matching_local_record_via_order_id(): void
    {
        Event::fake();

        $paymentLink = PaymentLink::create([
            'razorpay_id' => 'plink_2',
            'order_id' => 'order_2',
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => \Laraditz\Razorpay\Enums\PaymentLinkStatus::Created,
        ]);

        $payload = [
            'event' => 'payment.failed',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_2', 'order_id' => 'order_2']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentFailed::class, function ($event) use ($paymentLink, $payload) {
            return $event->paymentLink->is($paymentLink) && $event->payload === $payload;
        });
    }

    public function test_payment_failed_dispatches_with_null_model_when_no_match(): void
    {
        Event::fake();

        $payload = [
            'event' => 'payment.failed',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_2', 'order_id' => 'order_does_not_exist']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentFailed::class, function ($event) use ($payload) {
            return $event->paymentLink === null && $event->payload === $payload;
        });
    }

    public function test_order_paid_dispatches_with_matching_local_record(): void
    {
        Event::fake();

        $order = Order::create([
            'razorpay_id' => 'order_1',
            'amount' => 50000,
            'currency' => 'MYR',
            'status' => OrderStatus::Created,
        ]);

        $payload = [
            'event' => 'order.paid',
            'payload' => ['order' => ['entity' => ['id' => 'order_1']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(OrderPaid::class, function ($event) use ($order, $payload) {
            return $event->order->is($order) && $event->payload === $payload;
        });
    }

    public function test_order_paid_dispatches_with_null_model_when_no_match(): void
    {
        Event::fake();

        $payload = [
            'event' => 'order.paid',
            'payload' => ['order' => ['entity' => ['id' => 'order_does_not_exist']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(OrderPaid::class, function ($event) use ($payload) {
            return $event->order === null && $event->payload === $payload;
        });
    }

    protected function makeRefund(string $razorpayId): Refund
    {
        return Refund::create([
            'razorpay_id' => $razorpayId,
            'payment_id' => 'pay_1',
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => RefundStatus::Pending,
        ]);
    }

    public function test_refund_created_dispatches_with_matching_local_record(): void
    {
        Event::fake();

        $refund = $this->makeRefund('rfnd_1');

        $payload = [
            'event' => 'refund.created',
            'payload' => ['refund' => ['entity' => ['id' => 'rfnd_1']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(RefundCreated::class, function ($event) use ($refund, $payload) {
            return $event->refund->is($refund) && $event->payload === $payload;
        });
    }

    public function test_refund_processed_dispatches_with_matching_local_record(): void
    {
        Event::fake();

        $refund = $this->makeRefund('rfnd_2');

        $payload = [
            'event' => 'refund.processed',
            'payload' => ['refund' => ['entity' => ['id' => 'rfnd_2']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(RefundProcessed::class, function ($event) use ($refund, $payload) {
            return $event->refund->is($refund) && $event->payload === $payload;
        });
    }

    public function test_refund_failed_dispatches_with_matching_local_record(): void
    {
        Event::fake();

        $refund = $this->makeRefund('rfnd_3');

        $payload = [
            'event' => 'refund.failed',
            'payload' => ['refund' => ['entity' => ['id' => 'rfnd_3']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(RefundFailed::class, function ($event) use ($refund, $payload) {
            return $event->refund->is($refund) && $event->payload === $payload;
        });
    }

    public function test_refund_events_dispatch_with_null_model_when_no_match(): void
    {
        Event::fake();

        $payload = [
            'event' => 'refund.created',
            'payload' => ['refund' => ['entity' => ['id' => 'rfnd_does_not_exist']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(RefundCreated::class, function ($event) use ($payload) {
            return $event->refund === null && $event->payload === $payload;
        });
    }
}
