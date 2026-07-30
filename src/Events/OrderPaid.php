<?php

namespace Laraditz\Razorpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Razorpay\Models\Order;

class OrderPaid
{
    use Dispatchable, SerializesModels;

    public ?Order $order;
    public array $payload;

    public function __construct(?Order $order, array $payload)
    {
        $this->order = $order;
        $this->payload = $payload;
    }
}
