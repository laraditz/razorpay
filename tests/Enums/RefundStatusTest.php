<?php

namespace Laraditz\Razorpay\Tests\Enums;

use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Tests\TestCase;

class RefundStatusTest extends TestCase
{
    public function test_backing_values_match_razorpays_own_status_strings(): void
    {
        $this->assertSame('pending', RefundStatus::Pending->value);
        $this->assertSame('processed', RefundStatus::Processed->value);
        $this->assertSame('failed', RefundStatus::Failed->value);
    }

    public function test_is_processed(): void
    {
        $this->assertTrue(RefundStatus::Processed->isProcessed());
        $this->assertFalse(RefundStatus::Pending->isProcessed());
        $this->assertFalse(RefundStatus::Failed->isProcessed());
    }

    public function test_is_final(): void
    {
        $this->assertTrue(RefundStatus::Processed->isFinal());
        $this->assertTrue(RefundStatus::Failed->isFinal());
        $this->assertFalse(RefundStatus::Pending->isFinal());
    }
}
