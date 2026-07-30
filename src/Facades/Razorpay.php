<?php

namespace Laraditz\Razorpay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Laraditz\Razorpay\Services\PaymentLinkService paymentLink()
 * @method static \Laraditz\Razorpay\Services\OrderService order()
 * @method static \Laraditz\Razorpay\Client\RazorpayClient client()
 *
 * @see \Laraditz\Razorpay\Razorpay
 */
class Razorpay extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'razorpay';
    }
}
