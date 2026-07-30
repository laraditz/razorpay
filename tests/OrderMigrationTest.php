<?php

namespace Laraditz\Razorpay\Tests;

use Illuminate\Support\Facades\Schema;

class OrderMigrationTest extends TestCase
{
    public function test_razorpay_orders_table_has_every_documented_column(): void
    {
        $columns = Schema::getColumnListing('razorpay_orders');

        $expected = [
            'id',
            'razorpay_id',
            'status',
            'amount',
            'amount_paid',
            'amount_due',
            'currency',
            'receipt',
            'attempts',
            'notes',
            'raw_response',
            'paid_at',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        foreach ($expected as $column) {
            $this->assertContains($column, $columns, "Missing column: {$column}");
        }
    }
}
