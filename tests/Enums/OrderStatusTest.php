<?php

namespace Laraditz\Razorpay\Tests\Enums;

use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Tests\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_backing_values_match_razorpays_own_status_strings(): void
    {
        $this->assertSame('created', OrderStatus::Created->value);
        $this->assertSame('attempted', OrderStatus::Attempted->value);
        $this->assertSame('paid', OrderStatus::Paid->value);
    }

    public function test_is_paid(): void
    {
        $this->assertTrue(OrderStatus::Paid->isPaid());
        $this->assertFalse(OrderStatus::Attempted->isPaid());
        $this->assertFalse(OrderStatus::Created->isPaid());
    }

    public function test_is_final(): void
    {
        $this->assertTrue(OrderStatus::Paid->isFinal());
        $this->assertFalse(OrderStatus::Attempted->isFinal());
        $this->assertFalse(OrderStatus::Created->isFinal());
    }
}
