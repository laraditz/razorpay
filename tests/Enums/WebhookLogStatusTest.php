<?php

namespace Laraditz\Razorpay\Tests\Enums;

use Laraditz\Razorpay\Enums\WebhookLogStatus;
use Laraditz\Razorpay\Tests\TestCase;

class WebhookLogStatusTest extends TestCase
{
    public function test_cases_resolve_to_documented_string_values(): void
    {
        $this->assertSame('processed', WebhookLogStatus::Processed->value);
        $this->assertSame('unrecognized_event', WebhookLogStatus::UnrecognizedEvent->value);
        $this->assertSame('processing_failed', WebhookLogStatus::ProcessingFailed->value);
    }
}
