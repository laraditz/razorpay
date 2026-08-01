<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\WebhookLogStatus;
use Laraditz\Razorpay\Models\RazorpayWebhookLog;
use Laraditz\Razorpay\Tests\TestCase;

class WebhookLogTest extends TestCase
{
    public function test_it_casts_fields_correctly(): void
    {
        $log = RazorpayWebhookLog::create([
            'event_type' => 'payment_link.paid',
            'status' => WebhookLogStatus::Processed,
            'payload' => ['event' => 'payment_link.paid', 'payload' => []],
            'reference_id' => 'plink_test123',
            'error_message' => null,
        ]);

        $log->refresh();

        $this->assertSame('payment_link.paid', $log->event_type);
        $this->assertInstanceOf(WebhookLogStatus::class, $log->status);
        $this->assertSame(WebhookLogStatus::Processed, $log->status);
        $this->assertIsArray($log->payload);
        $this->assertSame(['event' => 'payment_link.paid', 'payload' => []], $log->payload);
        $this->assertSame('plink_test123', $log->reference_id);
        $this->assertNull($log->error_message);
    }
}
