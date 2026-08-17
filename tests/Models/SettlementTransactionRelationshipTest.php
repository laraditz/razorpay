<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Models\RazorpaySettlement;
use Laraditz\Razorpay\Models\RazorpaySettlementTransaction;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementTransactionRelationshipTest extends TestCase
{
    public function test_settlement_has_many_transactions(): void
    {
        $settlement = RazorpaySettlement::create([
            'razorpay_id' => 'setl_1',
            'amount' => 100000,
            'status' => 'processed',
        ]);

        RazorpaySettlementTransaction::create([
            'entity_id' => 'pay_1', 'type' => 'payment', 'settlement_id' => 'setl_1',
        ]);
        RazorpaySettlementTransaction::create([
            'entity_id' => 'pay_2', 'type' => 'payment', 'settlement_id' => 'setl_1',
        ]);
        RazorpaySettlementTransaction::create([
            'entity_id' => 'pay_3', 'type' => 'payment', 'settlement_id' => 'setl_other',
        ]);

        $this->assertCount(2, $settlement->transactions);
    }

    public function test_transaction_belongs_to_settlement(): void
    {
        RazorpaySettlement::create([
            'razorpay_id' => 'setl_1',
            'amount' => 100000,
            'status' => 'processed',
        ]);

        $transaction = RazorpaySettlementTransaction::create([
            'entity_id' => 'pay_1', 'type' => 'payment', 'settlement_id' => 'setl_1',
        ]);

        $this->assertNotNull($transaction->settlement);
        $this->assertSame('setl_1', $transaction->settlement->razorpay_id);
    }

    public function test_transaction_settlement_is_null_when_no_local_match_exists(): void
    {
        $transaction = RazorpaySettlementTransaction::create([
            'entity_id' => 'pay_1', 'type' => 'payment', 'settlement_id' => 'setl_unknown',
        ]);

        $this->assertNull($transaction->settlement);
    }

    public function test_settlement_transactions_is_empty_when_none_exist(): void
    {
        $settlement = RazorpaySettlement::create([
            'razorpay_id' => 'setl_1',
            'amount' => 100000,
            'status' => 'processed',
        ]);

        $this->assertCount(0, $settlement->transactions);
    }
}
