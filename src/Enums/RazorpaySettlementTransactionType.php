<?php

namespace Laraditz\Razorpay\Enums;

enum RazorpaySettlementTransactionType: string
{
    case Payment = 'payment';
    case Refund = 'refund';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';
}
