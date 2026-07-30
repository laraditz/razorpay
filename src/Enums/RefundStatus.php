<?php

namespace Laraditz\Razorpay\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';

    public function isProcessed(): bool
    {
        return $this === self::Processed;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Processed, self::Failed], true);
    }
}
