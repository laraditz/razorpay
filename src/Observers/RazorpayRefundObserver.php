<?php

namespace Laraditz\Razorpay\Observers;

use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Models\RazorpayRefund;

class RazorpayRefundObserver
{
    /**
     * Handle the RazorpayRefund "creating" event.
     */
    public function creating(RazorpayRefund $refund): void
    {
        if (is_null($refund->status)) {
            $refund->status = RefundStatus::Pending;
        }

        if (is_null($refund->currency)) {
            $refund->currency = config('razorpay.default_currency');
        }
    }
}
