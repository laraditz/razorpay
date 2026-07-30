<?php

namespace Laraditz\Razorpay\Tests;

use Illuminate\Support\Facades\Schema;

class RefundMigrationTest extends TestCase
{
    public function test_razorpay_refunds_table_has_every_documented_column(): void
    {
        $columns = Schema::getColumnListing('razorpay_refunds');

        $expected = [
            'id',
            'razorpay_id',
            'payment_id',
            'status',
            'amount',
            'currency',
            'notes',
            'receipt',
            'speed_requested',
            'speed_processed',
            'raw_response',
            'processed_at',
            'failed_at',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        foreach ($expected as $column) {
            $this->assertContains($column, $columns, "Missing column: {$column}");
        }
    }
}
