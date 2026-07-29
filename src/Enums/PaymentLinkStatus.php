<?php

namespace Laraditz\Razorpay\Enums;

enum PaymentLinkStatus: string
{
    case Created = 'created';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Expired, self::Cancelled], true);
    }
}
