<?php

namespace Laraditz\Razorpay\Tests\Support\Concerns;

use Laraditz\Razorpay\Enums\WebhookLogStatus;
use Laraditz\Razorpay\Models\RazorpayWebhookLog;
use Laraditz\Razorpay\Support\Concerns\LogsWebhookCalls;
use Laraditz\Razorpay\Tests\TestCase;

class LogsWebhookCallsTest extends TestCase
{
    protected function makeLogger()
    {
        return new class {
            use LogsWebhookCalls;

            public function callLogWebhookCall(string $eventType, WebhookLogStatus $status, array $payload, ?string $referenceId, ?string $errorMessage = null): void
            {
                $this->logWebhookCall($eventType, $status, $payload, $referenceId, $errorMessage);
            }
        };
    }

    public function test_log_webhook_call_creates_a_row_with_given_fields(): void
    {
        $payload = ['event' => 'payment_link.paid', 'payload' => []];

        $this->makeLogger()->callLogWebhookCall('payment_link.paid', WebhookLogStatus::Processed, $payload, 'plink_1', null);

        $log = RazorpayWebhookLog::first();

        $this->assertNotNull($log);
        $this->assertSame('payment_link.paid', $log->event_type);
        $this->assertSame(WebhookLogStatus::Processed, $log->status);
        $this->assertSame($payload, $log->payload);
        $this->assertSame('plink_1', $log->reference_id);
        $this->assertNull($log->error_message);
    }

    public function test_log_webhook_call_captures_error_message(): void
    {
        $payload = ['event' => 'order.paid', 'payload' => []];

        $this->makeLogger()->callLogWebhookCall('order.paid', WebhookLogStatus::ProcessingFailed, $payload, 'order_1', 'boom');

        $log = RazorpayWebhookLog::first();

        $this->assertSame(WebhookLogStatus::ProcessingFailed, $log->status);
        $this->assertSame('boom', $log->error_message);
    }

    public function test_no_log_row_created_when_logging_disabled(): void
    {
        config(['razorpay.log_webhook_calls' => false]);

        $this->makeLogger()->callLogWebhookCall('payment_link.paid', WebhookLogStatus::Processed, ['event' => 'payment_link.paid'], 'plink_1');

        $this->assertSame(0, RazorpayWebhookLog::count());
    }

    public function test_log_row_created_when_logging_enabled_by_default(): void
    {
        $this->makeLogger()->callLogWebhookCall('payment_link.paid', WebhookLogStatus::Processed, ['event' => 'payment_link.paid'], 'plink_1');

        $this->assertSame(1, RazorpayWebhookLog::count());
    }
}
