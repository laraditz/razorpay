<?php

namespace Laraditz\Razorpay\Tests\Models;

use Illuminate\Support\Carbon;
use Laraditz\Razorpay\Enums\WebhookLogStatus;
use Laraditz\Razorpay\Models\RazorpayWebhookLog;
use Laraditz\Razorpay\Tests\TestCase;

class WebhookLogPrunableTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_prunable_includes_rows_older_than_retention_window(): void
    {
        config(['razorpay.webhook_log_retention_days' => 30]);

        Carbon::setTestNow(now()->subDays(31));
        $oldLog = RazorpayWebhookLog::create([
            'event_type' => 'payment_link.paid',
            'status' => WebhookLogStatus::Processed,
            'payload' => ['event' => 'payment_link.paid'],
        ]);
        Carbon::setTestNow();

        $this->assertTrue((new RazorpayWebhookLog())->prunable()->pluck('id')->contains($oldLog->id));
    }

    public function test_prunable_excludes_rows_within_retention_window(): void
    {
        config(['razorpay.webhook_log_retention_days' => 30]);

        $recentLog = RazorpayWebhookLog::create([
            'event_type' => 'payment_link.paid',
            'status' => WebhookLogStatus::Processed,
            'payload' => ['event' => 'payment_link.paid'],
        ]);

        $this->assertFalse((new RazorpayWebhookLog())->prunable()->pluck('id')->contains($recentLog->id));
    }
}
