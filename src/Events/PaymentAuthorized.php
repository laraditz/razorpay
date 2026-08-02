<?php

namespace Laraditz\Razorpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Razorpay\Models\RazorpayPayment;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;

class PaymentAuthorized
{
    use Dispatchable, SerializesModels;

    public ?RazorpayPaymentLink $paymentLink;
    public ?RazorpayPayment $payment;
    public array $payload;

    public function __construct(?RazorpayPaymentLink $paymentLink, ?RazorpayPayment $payment, array $payload)
    {
        $this->paymentLink = $paymentLink;
        $this->payment = $payment;
        $this->payload = $payload;
    }
}
