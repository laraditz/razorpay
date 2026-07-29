<?php

namespace Laraditz\Razorpay\Tests;

use Illuminate\Support\Facades\Schema;

class PaymentLinkMigrationTest extends TestCase
{
    public function test_razorpay_payment_links_table_has_every_documented_column(): void
    {
        $columns = Schema::getColumnListing('razorpay_payment_links');

        $expected = [
            'id',
            'razorpay_id',
            'order_id',
            'status',
            'amount',
            'amount_paid',
            'currency',
            'reference_id',
            'description',
            'customer_name',
            'customer_email',
            'customer_contact',
            'notify_sms',
            'notify_email',
            'reminder_enable',
            'accept_partial',
            'first_min_partial_amount',
            'notes',
            'callback_url',
            'callback_method',
            'short_url',
            'raw_response',
            'expire_by',
            'paid_at',
            'cancelled_at',
            'expired_at',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        foreach ($expected as $column) {
            $this->assertContains($column, $columns, "Missing column: {$column}");
        }
    }
}
