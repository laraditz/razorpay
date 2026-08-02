<?php

namespace Laraditz\Razorpay\Tests\Support;

use Illuminate\Support\Facades\Event;
use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Enums\PaymentStatus;
use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Enums\WebhookLogStatus;
use Laraditz\Razorpay\Events\OrderPaid;
use Laraditz\Razorpay\Events\PaymentAuthorized;
use Laraditz\Razorpay\Events\PaymentCaptured;
use Laraditz\Razorpay\Events\PaymentFailed;
use Laraditz\Razorpay\Events\PaymentLinkPaid;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Events\RefundCreated;
use Laraditz\Razorpay\Events\RefundFailed;
use Laraditz\Razorpay\Events\RefundProcessed;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Models\RazorpayPayment;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;
use Laraditz\Razorpay\Models\RazorpayRefund;
use Laraditz\Razorpay\Models\RazorpayWebhookLog;
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

    public function test_unknown_event_type_logs_unrecognized_event(): void
    {
        $payload = ['event' => 'some.future.event', 'payload' => []];

        (new WebhookHandler())->handle($payload);

        $log = RazorpayWebhookLog::first();

        $this->assertNotNull($log);
        $this->assertSame('some.future.event', $log->event_type);
        $this->assertSame(WebhookLogStatus::UnrecognizedEvent, $log->status);
        $this->assertNull($log->reference_id);
    }

    public function test_known_event_type_logs_processed_with_reference_id(): void
    {
        $payload = [
            'event' => 'payment_link.paid',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_1']]],
        ];

        (new WebhookHandler())->handle($payload);

        $log = RazorpayWebhookLog::first();

        $this->assertNotNull($log);
        $this->assertSame(WebhookLogStatus::Processed, $log->status);
        $this->assertSame('plink_1', $log->reference_id);
        $this->assertNull($log->error_message);
    }

    public function test_handler_exception_logs_processing_failed_and_still_propagates(): void
    {
        Event::listen(PaymentLinkPaid::class, function () {
            throw new \RuntimeException('listener boom');
        });

        $payload = [
            'event' => 'payment_link.paid',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_1']]],
        ];

        try {
            (new WebhookHandler())->handle($payload);
            $this->fail('Expected the listener exception to propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('listener boom', $e->getMessage());
        }

        $log = RazorpayWebhookLog::first();

        $this->assertNotNull($log);
        $this->assertSame(WebhookLogStatus::ProcessingFailed, $log->status);
        $this->assertSame('listener boom', $log->error_message);
        $this->assertSame('plink_1', $log->reference_id);
    }


    public function test_payment_link_paid_dispatches_with_matching_local_record(): void
    {
        Event::fake();

        $paymentLink = RazorpayPaymentLink::create([
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

    public function test_payment_authorized_dispatches_and_syncs_local_payment(): void
    {
        Event::fake();

        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_auth',
            'order_id' => 'order_auth',
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => \Laraditz\Razorpay\Enums\PaymentLinkStatus::Created,
        ]);

        $payload = [
            'event' => 'payment.authorized',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_auth',
                        'order_id' => 'order_auth',
                        'status' => 'authorized',
                        'amount' => 1000,
                        'currency' => 'MYR',
                    ],
                ],
            ],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentAuthorized::class, function ($event) use ($paymentLink, $payload) {
            return $event->paymentLink->is($paymentLink)
                && $event->payment->razorpay_id === 'pay_auth'
                && $event->payload === $payload;
        });

        $payment = RazorpayPayment::where('razorpay_id', 'pay_auth')->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::Authorized, $payment->status);
    }

    public function test_payment_authorized_dispatches_with_null_payment_link_when_order_id_missing(): void
    {
        Event::fake();

        $payload = [
            'event' => 'payment.authorized',
            'payload' => [
                'payment' => ['entity' => ['id' => 'pay_auth2', 'status' => 'authorized', 'amount' => 1000, 'currency' => 'MYR']],
            ],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentAuthorized::class, function ($event) use ($payload) {
            return $event->paymentLink === null && $event->payload === $payload;
        });
    }

    public function test_payment_captured_dispatches_with_matching_local_record_via_order_id(): void
    {
        Event::fake();

        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_1',
            'order_id' => 'order_1',
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => \Laraditz\Razorpay\Enums\PaymentLinkStatus::Created,
        ]);

        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_1', 'order_id' => 'order_1', 'status' => 'captured', 'amount' => 1000, 'currency' => 'MYR']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentCaptured::class, function ($event) use ($paymentLink, $payload) {
            return $event->paymentLink->is($paymentLink)
                && $event->payment->razorpay_id === 'pay_1'
                && $event->payload === $payload;
        });
    }

    public function test_payment_captured_dispatches_with_null_model_when_order_id_missing(): void
    {
        Event::fake();

        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_1', 'status' => 'captured', 'amount' => 1000, 'currency' => 'MYR']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentCaptured::class, function ($event) use ($payload) {
            return $event->paymentLink === null && $event->payload === $payload;
        });
    }

    public function test_payment_failed_dispatches_with_matching_local_record_via_order_id(): void
    {
        Event::fake();

        $paymentLink = RazorpayPaymentLink::create([
            'razorpay_id' => 'plink_2',
            'order_id' => 'order_2',
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => \Laraditz\Razorpay\Enums\PaymentLinkStatus::Created,
        ]);

        $payload = [
            'event' => 'payment.failed',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_2', 'order_id' => 'order_2', 'status' => 'failed', 'amount' => 1000, 'currency' => 'MYR']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentFailed::class, function ($event) use ($paymentLink, $payload) {
            return $event->paymentLink->is($paymentLink)
                && $event->payment->razorpay_id === 'pay_2'
                && $event->payload === $payload;
        });
    }

    public function test_payment_failed_dispatches_with_null_model_when_no_match(): void
    {
        Event::fake();

        $payload = [
            'event' => 'payment.failed',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_2', 'order_id' => 'order_does_not_exist', 'status' => 'failed', 'amount' => 1000, 'currency' => 'MYR']]],
        ];

        (new WebhookHandler())->handle($payload);

        Event::assertDispatched(PaymentFailed::class, function ($event) use ($payload) {
            return $event->paymentLink === null && $event->payload === $payload;
        });
    }

    public function test_order_paid_dispatches_with_matching_local_record(): void
    {
        Event::fake();

        $order = RazorpayOrder::create([
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

    protected function makeRefund(string $razorpayId): RazorpayRefund
    {
        return RazorpayRefund::create([
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
