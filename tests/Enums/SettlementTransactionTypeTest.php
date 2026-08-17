<?php

namespace Laraditz\Razorpay\Tests\Enums;

use Laraditz\Razorpay\Enums\RazorpaySettlementTransactionType;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementTransactionTypeTest extends TestCase
{
    public function test_cases_resolve_to_documented_string_values(): void
    {
        $this->assertSame('payment', RazorpaySettlementTransactionType::Payment->value);
        $this->assertSame('refund', RazorpaySettlementTransactionType::Refund->value);
        $this->assertSame('transfer', RazorpaySettlementTransactionType::Transfer->value);
        $this->assertSame('adjustment', RazorpaySettlementTransactionType::Adjustment->value);
    }
}
