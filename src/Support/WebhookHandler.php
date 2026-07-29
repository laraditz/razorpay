<?php

namespace Laraditz\Razorpay\Support;

use Laraditz\Razorpay\Events\PaymentCaptured;
use Laraditz\Razorpay\Events\PaymentFailed;
use Laraditz\Razorpay\Events\PaymentLinkPaid;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Models\PaymentLink;

class WebhookHandler
{
    /**
     * Handle a verified incoming webhook payload.
     */
    public function handle(array $payload): void
    {
        $eventType = $this->getEventType($payload);

        event(new RazorpayWebhookReceived($eventType, $payload));

        match ($eventType) {
            'payment_link.paid' => $this->handlePaymentLinkPaid($payload),
            'payment.captured' => $this->handlePaymentCaptured($payload),
            'payment.failed' => $this->handlePaymentFailed($payload),
            default => null,
        };
    }

    protected function handlePaymentLinkPaid(array $payload): void
    {
        $razorpayId = data_get($payload, 'payload.payment_link.entity.id');
        $paymentLink = $razorpayId ? PaymentLink::where('razorpay_id', $razorpayId)->first() : null;

        event(new PaymentLinkPaid($paymentLink, $payload));
    }

    protected function handlePaymentCaptured(array $payload): void
    {
        event(new PaymentCaptured($this->findByOrderId($payload), $payload));
    }

    protected function handlePaymentFailed(array $payload): void
    {
        event(new PaymentFailed($this->findByOrderId($payload), $payload));
    }

    protected function findByOrderId(array $payload): ?PaymentLink
    {
        $orderId = data_get($payload, 'payload.payment.entity.order_id');

        return $orderId ? PaymentLink::where('order_id', $orderId)->first() : null;
    }

    protected function getEventType(array $payload): string
    {
        return $payload['event'] ?? 'unknown';
    }
}
