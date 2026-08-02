<?php

namespace Laraditz\Razorpay\Tests\Database;

use Illuminate\Support\Facades\Schema;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementMigrationTest extends TestCase
{
    public function test_razorpay_settlements_table_has_all_documented_columns(): void
    {
        $columns = Schema::getColumnListing('razorpay_settlements');

        foreach ([
            'id', 'razorpay_id', 'amount', 'fees', 'tax', 'utr', 'status',
            'settled_at', 'raw_response', 'created_at', 'updated_at',
        ] as $expected) {
            $this->assertContains($expected, $columns);
        }
    }
}
