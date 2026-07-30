<?php

namespace Laraditz\Razorpay\Enums;

enum OrderStatus: string
{
    case Created = 'created';
    case Attempted = 'attempted';
    case Paid = 'paid';

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isFinal(): bool
    {
        return $this === self::Paid;
    }
}
