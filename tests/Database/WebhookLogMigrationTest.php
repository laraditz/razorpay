<?php

namespace Laraditz\Razorpay\Tests\Database;

use Illuminate\Support\Facades\Schema;
use Laraditz\Razorpay\Tests\TestCase;

class WebhookLogMigrationTest extends TestCase
{
    public function test_razorpay_webhook_logs_table_has_all_documented_columns(): void
    {
        $columns = Schema::getColumnListing('razorpay_webhook_logs');

        foreach (['id', 'event_type', 'status', 'payload', 'reference_id', 'error_message', 'created_at', 'updated_at'] as $expected) {
            $this->assertContains($expected, $columns);
        }
    }
}
