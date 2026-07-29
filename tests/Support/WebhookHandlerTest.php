<?php

namespace Laraditz\Razorpay\Tests\Support;

use Illuminate\Support\Facades\Event;
use Laraditz\Razorpay\Events\PaymentCaptured;
use Laraditz\Razorpay\Events\PaymentFailed;
use Laraditz\Razorpay\Events\PaymentLinkPaid;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
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
}
