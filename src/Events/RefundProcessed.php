<?php

namespace Laraditz\Razorpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Razorpay\Models\Refund;

class RefundProcessed
{
    use Dispatchable, SerializesModels;

    public ?Refund $refund;
    public array $payload;

    public function __construct(?Refund $refund, array $payload)
    {
        $this->refund = $refund;
        $this->payload = $payload;
    }
}
