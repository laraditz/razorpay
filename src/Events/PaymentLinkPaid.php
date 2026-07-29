<?php

namespace Laraditz\Razorpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Razorpay\Models\PaymentLink;

class PaymentLinkPaid
{
    use Dispatchable, SerializesModels;

    public ?PaymentLink $paymentLink;
    public array $payload;

    public function __construct(?PaymentLink $paymentLink, array $payload)
    {
        $this->paymentLink = $paymentLink;
        $this->payload = $payload;
    }
}
