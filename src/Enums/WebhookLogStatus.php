<?php

namespace Laraditz\Razorpay\Enums;

enum WebhookLogStatus: string
{
    case Processed = 'processed';
    case UnrecognizedEvent = 'unrecognized_event';
    case ProcessingFailed = 'processing_failed';
}
