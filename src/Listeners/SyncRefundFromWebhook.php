<?php

namespace Laraditz\Razorpay\Listeners;

use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Models\RazorpayRefund;

class SyncRefundFromWebhook
{
    protected const HANDLED_EVENTS = ['refund.created', 'refund.processed', 'refund.failed'];

    public function handle(RazorpayWebhookReceived $event): void
    {
        if (!in_array($event->eventType, self::HANDLED_EVENTS, true)) {
            return;
        }

        $razorpayId = data_get($event->payload, 'payload.refund.entity.id');

        if (!$razorpayId) {
            return;
        }

        $refund = RazorpayRefund::where('razorpay_id', $razorpayId)->first();

        if (!$refund) {
            return;
        }

        // The target status comes from the payload's own status field, not
        // a hardcoded event-name mapping: Razorpay's refund.created payload
        // can already carry status "processed" (e.g. instant refunds), so
        // the event name alone doesn't reliably determine the target state.
        $statusValue = data_get($event->payload, 'payload.refund.entity.status');
        $status = $statusValue ? RefundStatus::tryFrom($statusValue) : null;

        if (!$status || $refund->status === $status) {
            return;
        }

        $refund->update([
            'status' => $status,
            'speed_processed' => data_get($event->payload, 'payload.refund.entity.speed_processed'),
            'processed_at' => $status === RefundStatus::Processed ? now() : null,
            'failed_at' => $status === RefundStatus::Failed ? now() : null,
        ]);
    }
}
