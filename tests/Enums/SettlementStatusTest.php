<?php

namespace Laraditz\Razorpay\Tests\Enums;

use Laraditz\Razorpay\Enums\SettlementStatus;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementStatusTest extends TestCase
{
    public function test_cases_resolve_to_documented_string_values(): void
    {
        $this->assertSame('created', SettlementStatus::Created->value);
        $this->assertSame('processed', SettlementStatus::Processed->value);
        $this->assertSame('failed', SettlementStatus::Failed->value);
    }
}
