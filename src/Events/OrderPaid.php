<?php

namespace Laraditz\Razorpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Razorpay\Models\RazorpayOrder;

class OrderPaid
{
    use Dispatchable, SerializesModels;

    public ?RazorpayOrder $order;
    public array $payload;

    public function __construct(?RazorpayOrder $order, array $payload)
    {
        $this->order = $order;
        $this->payload = $payload;
    }
}
