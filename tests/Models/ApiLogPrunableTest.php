<?php

namespace Laraditz\Razorpay\Tests\Models;

use Illuminate\Support\Carbon;
use Laraditz\Razorpay\Models\RazorpayApiLog;
use Laraditz\Razorpay\Tests\TestCase;

class ApiLogPrunableTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_prunable_includes_rows_older_than_retention_window(): void
    {
        config(['razorpay.api_log_retention_days' => 30]);

        Carbon::setTestNow(now()->subDays(31));
        $oldLog = RazorpayApiLog::create([
            'method' => 'GET',
            'endpoint' => '/payment_links',
            'http_status' => 200,
        ]);
        Carbon::setTestNow();

        $this->assertTrue((new RazorpayApiLog())->prunable()->pluck('id')->contains($oldLog->id));
    }

    public function test_prunable_excludes_rows_within_retention_window(): void
    {
        config(['razorpay.api_log_retention_days' => 30]);

        $recentLog = RazorpayApiLog::create([
            'method' => 'GET',
            'endpoint' => '/payment_links',
            'http_status' => 200,
        ]);

        $this->assertFalse((new RazorpayApiLog())->prunable()->pluck('id')->contains($recentLog->id));
    }
}
