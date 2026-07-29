<?php

namespace Laraditz\Razorpay\Tests\Enums;

use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentLinkStatusTest extends TestCase
{
    public function test_backing_values_match_razorpays_own_status_strings(): void
    {
        $this->assertSame('created', PaymentLinkStatus::Created->value);
        $this->assertSame('partially_paid', PaymentLinkStatus::PartiallyPaid->value);
        $this->assertSame('paid', PaymentLinkStatus::Paid->value);
        $this->assertSame('expired', PaymentLinkStatus::Expired->value);
        $this->assertSame('cancelled', PaymentLinkStatus::Cancelled->value);
    }

    public function test_is_paid(): void
    {
        $this->assertTrue(PaymentLinkStatus::Paid->isPaid());
        $this->assertFalse(PaymentLinkStatus::Created->isPaid());
    }

    public function test_is_final(): void
    {
        $this->assertTrue(PaymentLinkStatus::Paid->isFinal());
        $this->assertTrue(PaymentLinkStatus::Expired->isFinal());
        $this->assertTrue(PaymentLinkStatus::Cancelled->isFinal());
        $this->assertFalse(PaymentLinkStatus::Created->isFinal());
        $this->assertFalse(PaymentLinkStatus::PartiallyPaid->isFinal());
    }
}
