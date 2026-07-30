<?php

namespace Laraditz\Razorpay\Tests\Events;

use Laraditz\Razorpay\Events\PaymentCaptured;
use Laraditz\Razorpay\Events\PaymentFailed;
use Laraditz\Razorpay\Events\PaymentLinkPaid;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Models\PaymentLink;
use Laraditz\Razorpay\Tests\TestCase;

class WebhookEventsTest extends TestCase
{
    public function test_razorpay_webhook_received_stores_event_type_and_payload(): void
    {
        $event = new RazorpayWebhookReceived('payment_link.paid', ['event' => 'payment_link.paid']);

        $this->assertSame('payment_link.paid', $event->eventType);
        $this->assertSame(['event' => 'payment_link.paid'], $event->payload);
    }

    public function test_payment_link_paid_stores_model_and_payload(): void
    {
        $paymentLink = new PaymentLink(['razorpay_id' => 'plink_1']);
        $event = new PaymentLinkPaid($paymentLink, ['id' => 'plink_1']);

        $this->assertSame($paymentLink, $event->paymentLink);
        $this->assertSame(['id' => 'plink_1'], $event->payload);
    }

    public function test_payment_captured_stores_nullable_model_and_payload(): void
    {
        $event = new PaymentCaptured(null, ['id' => 'pay_1']);

        $this->assertNull($event->paymentLink);
        $this->assertSame(['id' => 'pay_1'], $event->payload);
    }

    public function test_payment_failed_stores_nullable_model_and_payload(): void
    {
        $event = new PaymentFailed(null, ['id' => 'pay_1']);

        $this->assertNull($event->paymentLink);
        $this->assertSame(['id' => 'pay_1'], $event->payload);
    }
}
