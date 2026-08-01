<?php

namespace Laraditz\Razorpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;

class PaymentLinkPaid
{
    use Dispatchable, SerializesModels;

    public ?RazorpayPaymentLink $paymentLink;
    public array $payload;

    public function __construct(?RazorpayPaymentLink $paymentLink, array $payload)
    {
        $this->paymentLink = $paymentLink;
        $this->payload = $payload;
    }
}
