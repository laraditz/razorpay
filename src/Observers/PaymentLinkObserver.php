<?php

namespace Laraditz\Razorpay\Observers;

use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;

class PaymentLinkObserver
{
    /**
     * Handle the RazorpayPaymentLink "creating" event.
     */
    public function creating(RazorpayPaymentLink $paymentLink): void
    {
        if (is_null($paymentLink->status)) {
            $paymentLink->status = PaymentLinkStatus::Created;
        }

        if (is_null($paymentLink->currency)) {
            $paymentLink->currency = config('razorpay.default_currency');
        }
    }
}
