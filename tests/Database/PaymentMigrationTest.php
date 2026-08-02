<?php

namespace Laraditz\Razorpay\Tests\Database;

use Illuminate\Support\Facades\Schema;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentMigrationTest extends TestCase
{
    public function test_razorpay_payments_table_has_all_documented_columns(): void
    {
        $columns = Schema::getColumnListing('razorpay_payments');

        foreach ([
            'id', 'razorpay_id', 'order_id', 'status', 'method', 'amount',
            'amount_refunded', 'currency', 'captured', 'description', 'email',
            'contact', 'notes', 'fee', 'tax', 'error_code', 'error_description',
            'raw_response', 'created_at', 'updated_at',
        ] as $expected) {
            $this->assertContains($expected, $columns);
        }
    }
}
