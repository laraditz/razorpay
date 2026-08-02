<?php

namespace Laraditz\Razorpay\Enums;

enum SettlementStatus: string
{
    case Created = 'created';
    case Processed = 'processed';
    case Failed = 'failed';
}
