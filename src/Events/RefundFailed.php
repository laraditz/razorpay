<?php

namespace Laraditz\Razorpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Razorpay\Models\RazorpayRefund;

class RefundFailed
{
    use Dispatchable, SerializesModels;

    public ?RazorpayRefund $refund;
    public array $payload;

    public function __construct(?RazorpayRefund $refund, array $payload)
    {
        $this->refund = $refund;
        $this->payload = $payload;
    }
}
