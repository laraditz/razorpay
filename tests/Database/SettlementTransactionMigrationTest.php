<?php

namespace Laraditz\Razorpay\Tests\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementTransactionMigrationTest extends TestCase
{
    public function test_razorpay_settlement_transactions_table_has_all_documented_columns(): void
    {
        $columns = Schema::getColumnListing('razorpay_settlement_transactions');

        foreach ([
            'id', 'entity_id', 'type', 'settlement_id',
            'debit', 'credit', 'amount', 'currency', 'fee', 'tax',
            'settled', 'on_hold', 'settled_at', 'transaction_created_at',
            'payment_id', 'order_id', 'order_receipt', 'settlement_utr', 'dispute_id',
            'method', 'card_network', 'card_issuer', 'card_type',
            'description', 'notes', 'posted_at', 'credit_type',
            'created_at', 'updated_at',
        ] as $expected) {
            $this->assertContains($expected, $columns);
        }
    }

    public function test_entity_id_and_type_have_a_unique_composite_index(): void
    {
        DB::table('razorpay_settlement_transactions')->insert([
            'entity_id' => 'pay_1', 'type' => 'payment', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('razorpay_settlement_transactions')->insert([
            'entity_id' => 'pay_1', 'type' => 'payment', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
