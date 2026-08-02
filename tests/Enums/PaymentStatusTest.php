<?php

namespace Laraditz\Razorpay\Tests\Enums;

use Laraditz\Razorpay\Enums\PaymentStatus;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentStatusTest extends TestCase
{
    public function test_cases_resolve_to_documented_string_values(): void
    {
        $this->assertSame('created', PaymentStatus::Created->value);
        $this->assertSame('authorized', PaymentStatus::Authorized->value);
        $this->assertSame('captured', PaymentStatus::Captured->value);
        $this->assertSame('refunded', PaymentStatus::Refunded->value);
        $this->assertSame('failed', PaymentStatus::Failed->value);
    }
}
