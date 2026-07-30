<?php

namespace Laraditz\Razorpay\Tests\Support;

use Illuminate\Support\Facades\Event;
use Laraditz\Razorpay\Events\PaymentCaptured;
use Laraditz\Razorpay\Events\PaymentFailed;
use Laraditz\Razorpay\Events\PaymentLinkPaid;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Models\PaymentLink;
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
}
