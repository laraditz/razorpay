<?php

namespace Laraditz\Razorpay\Support\Concerns;

use Laraditz\Razorpay\Enums\WebhookLogStatus;
use Laraditz\Razorpay\Models\RazorpayWebhookLog;

trait LogsWebhookCalls
{
    protected function logWebhookCall(string $eventType, WebhookLogStatus $status, array $payload, ?string $referenceId, ?string $errorMessage = null): void
    {
        if (!config('razorpay.log_webhook_calls', true)) {
            return;
        }

        RazorpayWebhookLog::create([
            'event_type' => $eventType,
            'status' => $status,
            'payload' => $payload,
            'reference_id' => $referenceId,
            'error_message' => $errorMessage,
        ]);
    }
}
