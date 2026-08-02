<?php

namespace Laraditz\Razorpay\Enums;

enum PaymentStatus: string
{
    case Created = 'created';
    case Authorized = 'authorized';
    case Captured = 'captured';
    case Refunded = 'refunded';
    case Failed = 'failed';
}
