<?php

namespace Laraditz\Razorpay\Support;

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
            default => null,
        };
    }

    protected function handlePaymentLinkPaid(array $payload): void
    {
        $razorpayId = data_get($payload, 'payload.payment_link.entity.id');
        $paymentLink = $razorpayId ? PaymentLink::where('razorpay_id', $razorpayId)->first() : null;

        event(new PaymentLinkPaid($paymentLink, $payload));
    }

    protected function getEventType(array $payload): string
    {
        return $payload['event'] ?? 'unknown';
    }
}
