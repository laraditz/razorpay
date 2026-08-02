# Logging

This package can optionally keep two separate audit trails: outbound API calls, and inbound webhook deliveries.

## API Request/Response Logging

Outbound calls made via `RazorpayClient` — across Payment Links, Orders, Payments, Refunds, and Settlements — can be recorded for troubleshooting and reconciliation, whether the call succeeds, fails with a non-2xx response, or fails to connect at all. Sensitive fields are protected before storage.

**Local Record:** `RazorpayApiLog` (`razorpay_api_logs` table). See the model source for the exact fields captured.

### Disabling

```env
RAZORPAY_LOG_API_CALLS=false
```

### Retention

```env
RAZORPAY_API_LOG_RETENTION_DAYS=30
```

Rows older than this are eligible for deletion via Laravel's built-in pruning — no custom command needed:

```php
// routes/console.php or app/Console/Kernel.php
Schedule::command('model:prune')->daily();
```

## Webhook Audit Log

Signature-verified incoming webhooks can be recorded for later audit, regardless of whether the event type was recognized or processing succeeded.

**Local Record:** `RazorpayWebhookLog` (`razorpay_webhook_logs` table). See the model source for the exact fields captured.

Requests that fail signature verification are **not** written to this table — they're handled separately, so a public endpoint's probe/scanner traffic doesn't pollute your audit trail. See [Webhooks](webhooks.md) for how signature verification itself works.

### Disabling

```env
RAZORPAY_LOG_WEBHOOK_CALLS=false
```

### Retention

```env
RAZORPAY_WEBHOOK_LOG_RETENTION_DAYS=30
```

Rows older than this are eligible for deletion via the same `model:prune` schedule as `RazorpayApiLog`.

## Usage Example

```php
use Laraditz\Razorpay\Models\RazorpayApiLog;
use Laraditz\Razorpay\Models\RazorpayWebhookLog;

// Find the slowest outbound calls today
RazorpayApiLog::whereDate('created_at', today())
    ->orderByDesc('duration_ms')
    ->limit(10)
    ->get();

// Find webhooks that failed to process
RazorpayWebhookLog::where('status', \Laraditz\Razorpay\Enums\WebhookLogStatus::ProcessingFailed)
    ->latest()
    ->get(['event_type', 'error_message', 'created_at']);
```

## Related Documentation

- [Webhooks](webhooks.md)
