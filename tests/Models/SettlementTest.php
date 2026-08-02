<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\SettlementStatus;
use Laraditz\Razorpay\Models\RazorpaySettlement;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementTest extends TestCase
{
    public function test_it_casts_fields_correctly(): void
    {
        $settlement = RazorpaySettlement::create([
            'razorpay_id' => 'setl_test123',
            'amount' => 100000,
            'fees' => 500,
            'tax' => 100,
            'utr' => 'UTR12345',
            'status' => SettlementStatus::Processed,
            'settled_at' => now(),
            'raw_response' => ['id' => 'setl_test123', 'status' => 'processed'],
        ]);

        $settlement->refresh();

        $this->assertSame('setl_test123', $settlement->razorpay_id);
        $this->assertInstanceOf(SettlementStatus::class, $settlement->status);
        $this->assertSame(SettlementStatus::Processed, $settlement->status);
        $this->assertIsInt($settlement->amount);
        $this->assertIsInt($settlement->fees);
        $this->assertIsInt($settlement->tax);
        $this->assertSame('UTR12345', $settlement->utr);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $settlement->settled_at);
        $this->assertIsArray($settlement->raw_response);
        $this->assertSame(['id' => 'setl_test123', 'status' => 'processed'], $settlement->raw_response);
    }
}
