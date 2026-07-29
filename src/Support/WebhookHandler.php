<?php

namespace Laraditz\Razorpay\Support;

use Laraditz\Razorpay\Events\RazorpayWebhookReceived;

class WebhookHandler
{
    /**
     * Handle a verified incoming webhook payload.
     */
    public function handle(array $payload): void
    {
        event(new RazorpayWebhookReceived($this->getEventType($payload), $payload));
    }

    protected function getEventType(array $payload): string
    {
        return $payload['event'] ?? 'unknown';
    }
}
