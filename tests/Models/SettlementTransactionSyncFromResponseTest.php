<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\RazorpaySettlementTransactionType;
use Laraditz\Razorpay\Models\RazorpaySettlementTransaction;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementTransactionSyncFromResponseTest extends TestCase
{
    protected function makeResponse(array $overrides = []): array
    {
        return array_merge([
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
            'created_at' => now()->timestamp,
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
        ], $overrides);
    }

    public function test_syncs_entity_id_and_type_uniquely(): void
    {
        $transaction = RazorpaySettlementTransaction::syncFromResponse($this->makeResponse());

        $this->assertNotNull($transaction);
        $this->assertSame('pay_1', $transaction->entity_id);
        $this->assertSame(RazorpaySettlementTransactionType::Payment, $transaction->type);
        $this->assertSame('setl_1', $transaction->settlement_id);
    }

    public function test_missing_entity_id_returns_null_and_writes_nothing(): void
    {
        $result = RazorpaySettlementTransaction::syncFromResponse($this->makeResponse(['entity_id' => null]));

        $this->assertNull($result);
        $this->assertSame(0, RazorpaySettlementTransaction::count());
    }

    public function test_missing_type_returns_null_and_writes_nothing(): void
    {
        $result = RazorpaySettlementTransaction::syncFromResponse($this->makeResponse(['type' => null]));

        $this->assertNull($result);
        $this->assertSame(0, RazorpaySettlementTransaction::count());
    }

    public function test_syncing_same_entity_id_and_type_updates_instead_of_duplicating(): void
    {
        RazorpaySettlementTransaction::syncFromResponse($this->makeResponse(['on_hold' => true]));
        RazorpaySettlementTransaction::syncFromResponse($this->makeResponse(['on_hold' => false]));

        $this->assertSame(1, RazorpaySettlementTransaction::count());
        $this->assertFalse(RazorpaySettlementTransaction::first()->on_hold);
    }

    public function test_response_created_at_maps_to_transaction_created_at_column(): void
    {
        $timestamp = now()->timestamp;

        $transaction = RazorpaySettlementTransaction::syncFromResponse($this->makeResponse(['created_at' => $timestamp]));

        $this->assertNotNull($transaction->transaction_created_at);
        $this->assertSame($timestamp, $transaction->transaction_created_at->timestamp);
    }

    public function test_created_at_zero_does_not_produce_an_epoch_date(): void
    {
        $transaction = RazorpaySettlementTransaction::syncFromResponse($this->makeResponse(['created_at' => 0]));

        $this->assertNotNull($transaction);
        $this->assertNull($transaction->transaction_created_at);
    }

    public function test_settled_at_zero_does_not_produce_an_epoch_date(): void
    {
        $transaction = RazorpaySettlementTransaction::syncFromResponse($this->makeResponse(['settled_at' => 0]));

        $this->assertNotNull($transaction);
        $this->assertNull($transaction->settled_at);
    }

    public function test_posted_at_zero_does_not_produce_an_epoch_date(): void
    {
        $transaction = RazorpaySettlementTransaction::syncFromResponse($this->makeResponse(['posted_at' => 0]));

        $this->assertNotNull($transaction);
        $this->assertNull($transaction->posted_at);
    }
}
