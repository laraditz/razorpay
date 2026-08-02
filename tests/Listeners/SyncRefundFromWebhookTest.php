<?php

namespace Laraditz\Razorpay\Tests\Listeners;

use Illuminate\Support\Carbon;
use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Listeners\SyncRefundFromWebhook;
use Laraditz\Razorpay\Models\RazorpayRefund;
use Laraditz\Razorpay\Tests\TestCase;

class SyncRefundFromWebhookTest extends TestCase
{
    protected function makeRefund(string $razorpayId = 'rfnd_1'): RazorpayRefund
    {
        return RazorpayRefund::create([
            'razorpay_id' => $razorpayId,
            'payment_id' => 'pay_1',
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => RefundStatus::Pending,
        ]);
    }

    protected function makeEvent(string $eventType, string $razorpayId, string $status, ?string $speedProcessed = null): RazorpayWebhookReceived
    {
        return new RazorpayWebhookReceived($eventType, [
            'event' => $eventType,
            'payload' => [
                'refund' => [
                    'entity' => [
                        'id' => $razorpayId,
                        'status' => $status,
                        'speed_processed' => $speedProcessed,
                    ],
                ],
            ],
        ]);
    }

    public function test_refund_processed_updates_status_speed_and_processed_at(): void
    {
        $refund = $this->makeRefund();

        (new SyncRefundFromWebhook())->handle($this->makeEvent('refund.processed', 'rfnd_1', 'processed', 'normal'));

        $refund->refresh();
        $this->assertSame(RefundStatus::Processed, $refund->status);
        $this->assertSame('normal', $refund->speed_processed);
        $this->assertNotNull($refund->processed_at);
        $this->assertNull($refund->failed_at);
    }

    public function test_refund_failed_updates_status_and_failed_at(): void
    {
        $refund = $this->makeRefund();

        (new SyncRefundFromWebhook())->handle($this->makeEvent('refund.failed', 'rfnd_1', 'failed'));

        $refund->refresh();
        $this->assertSame(RefundStatus::Failed, $refund->status);
        $this->assertNotNull($refund->failed_at);
        $this->assertNull($refund->processed_at);
    }

    public function test_refund_created_with_processed_status_in_payload_results_in_processed_not_pending(): void
    {
        // Razorpay's own docs show refund.created payloads can already carry
        // status: "processed" -- the listener must read the payload's status,
        // not assume "created" implies "pending".
        $refund = $this->makeRefund();

        (new SyncRefundFromWebhook())->handle($this->makeEvent('refund.created', 'rfnd_1', 'processed', 'optimum'));

        $refund->refresh();
        $this->assertSame(RefundStatus::Processed, $refund->status);
        $this->assertSame('optimum', $refund->speed_processed);
    }

    public function test_redelivering_the_same_event_is_idempotent(): void
    {
        $refund = $this->makeRefund();
        $listener = new SyncRefundFromWebhook();
        $event = $this->makeEvent('refund.processed', 'rfnd_1', 'processed', 'normal');

        Carbon::setTestNow('2026-01-01 00:00:00');
        $listener->handle($event);
        $refund->refresh();
        $firstProcessedAt = $refund->processed_at;

        Carbon::setTestNow('2026-01-01 00:05:00');
        $listener->handle($event);
        $refund->refresh();

        Carbon::setTestNow();

        $this->assertSame(RefundStatus::Processed, $refund->status);
        $this->assertSame($firstProcessedAt->toIso8601String(), $refund->processed_at->toIso8601String());
    }

    public function test_no_matching_local_record_does_not_throw(): void
    {
        $event = $this->makeEvent('refund.processed', 'rfnd_does_not_exist', 'processed');

        (new SyncRefundFromWebhook())->handle($event);

        $this->assertTrue(true);
    }
}
