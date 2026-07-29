<?php

namespace Laraditz\Razorpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RazorpayWebhookReceived
{
    use Dispatchable, SerializesModels;

    public string $eventType;
    public array $payload;

    public function __construct(string $eventType, array $payload)
    {
        $this->eventType = $eventType;
        $this->payload = $payload;
    }
}
