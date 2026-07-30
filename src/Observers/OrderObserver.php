<?php

namespace Laraditz\Razorpay\Observers;

use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "creating" event.
     */
    public function creating(Order $order): void
    {
        if (is_null($order->status)) {
            $order->status = OrderStatus::Created;
        }

        if (is_null($order->currency)) {
            $order->currency = config('razorpay.default_currency');
        }
    }
}
