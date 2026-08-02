<?php

namespace Laraditz\Razorpay\Tests;

use Illuminate\Support\Facades\Schema;

class ApiLogMigrationTest extends TestCase
{
    public function test_razorpay_api_logs_table_has_every_documented_column(): void
    {
        $columns = Schema::getColumnListing('razorpay_api_logs');

        $expected = [
            'id',
            'method',
            'endpoint',
            'reference_id',
            'request_payload',
            'response_payload',
            'http_status',
            'duration_ms',
            'created_at',
            'updated_at',
        ];

        foreach ($expected as $column) {
            $this->assertContains($column, $columns, "Missing column: {$column}");
        }
    }
}
