<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\RazorpaySettlementTransactionType;
use Laraditz\Razorpay\Models\RazorpaySettlementTransaction;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementTransactionTest extends TestCase
{
    public function test_create_and_cast_round_trip(): void
    {
        $transaction = RazorpaySettlementTransaction::create([
            'entity_id' => 'pay_1',
            'type' => 'payment',
            'settlement_id' => 'setl_1',
            'debit' => 0,
            'credit' => 100000,
            'amount' => 100000,
            'currency' => 'MYR',
            'fee' => 500,
            'tax' => 100,
            'settled' => true,
            'on_hold' => false,
            'settled_at' => now()->timestamp,
            'transaction_created_at' => now()->timestamp,
            'payment_id' => 'pay_1',
            'order_id' => 'order_1',
            'order_receipt' => 'receipt_1',
            'settlement_utr' => 'UTR1',
            'dispute_id' => null,
            'method' => 'card',
            'card_network' => 'Visa',
            'card_issuer' => 'HDFC',
            'card_type' => 'credit',
            'description' => 'Test payment',
            'notes' => ['foo' => 'bar'],
            'posted_at' => now()->timestamp,
            'credit_type' => 'default',
        ]);

        $this->assertInstanceOf(RazorpaySettlementTransactionType::class, $transaction->type);
        $this->assertSame(RazorpaySettlementTransactionType::Payment, $transaction->type);
        $this->assertIsBool($transaction->settled);
        $this->assertTrue($transaction->settled);
        $this->assertIsBool($transaction->on_hold);
        $this->assertFalse($transaction->on_hold);
        $this->assertIsArray($transaction->notes);
        $this->assertSame(['foo' => 'bar'], $transaction->notes);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $transaction->settled_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $transaction->transaction_created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $transaction->posted_at);
    }
}
