# Webhooks

Razorpay sends webhook notifications when events happen in your account — a payment link gets paid, a payment is captured, a refund processes, a settlement lands. This package automatically verifies, syncs, and dispatches typed Laravel events for all of them.

**Official Documentation:** https://curlec.com/docs/webhooks/

## Webhook Setup

### 1. Register the route

A webhook route is registered automatically at:

```
POST https://yourapp.com/razorpay/webhook
```

(configurable via `RAZORPAY_WEBHOOK_PATH`). It is **not** part of Laravel's `web` middleware group, so it is never subject to CSRF verification — the `X-Razorpay-Signature` header is the sole authentication boundary.

### 2. Point Razorpay at it

In your Razorpay Dashboard, add the webhook URL and set the same secret as `RAZORPAY_WEBHOOK_SECRET`.

```env
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret
```

### 3. Signature verification

Every request is verified (HMAC over the raw body) before any payload is processed. An invalid or missing signature is rejected with `401`.

## What Happens Automatically

1. Signature verified — reject with `401` if invalid/missing.
2. A generic `RazorpayWebhookReceived` event fires for **every** verified webhook, regardless of type — nothing is ever silently dropped, even for event types this package doesn't have typed handling for yet.
3. A typed event fires for the events this package currently understands, each matched to its local record(s) where possible.

## Available Events

| Event | Razorpay event | Local model(s) kept in sync |
|---|---|---|
| `PaymentLinkPaid` | `payment_link.paid` | `RazorpayPaymentLink` |
| `PaymentAuthorized` | `payment.authorized` | `RazorpayPaymentLink` (via `order_id`) + `RazorpayPayment` |
| `PaymentCaptured` | `payment.captured` | `RazorpayPaymentLink` (via `order_id`) + `RazorpayPayment` |
| `PaymentFailed` | `payment.failed` | `RazorpayPaymentLink` (via `order_id`) + `RazorpayPayment` |
| `OrderPaid` | `order.paid` | `RazorpayOrder` |
| `RefundCreated` | `refund.created` | `RazorpayRefund` |
| `RefundProcessed` | `refund.processed` | `RazorpayRefund` |
| `RefundFailed` | `refund.failed` | `RazorpayRefund` |
| `SettlementProcessed` | `settlement.processed` | `RazorpaySettlement` |
| `RazorpayWebhookReceived` | any | — (generic, fires first, always) |

If no local record matches (e.g. the resource wasn't created through this package), typed events still dispatch with a `null` model instead of throwing — always check for `null` before using it.

`PaymentAuthorized`, `PaymentCaptured`, and `PaymentFailed` each carry both `$paymentLink` (nullable, matched via the underlying order) and `$payment` (nullable, the synced `RazorpayPayment` record) — check whichever one your listener actually needs.

> Razorpay's `refund.created` webhook can arrive with a payload that already reports `status: "processed"` (e.g. instant refunds). This package's sync logic reads the actual status from the payload rather than assuming `pending` from the event name, so your local record is always correct regardless of which event delivered it.

## Listening to Events

### Register in your EventServiceProvider

```php
use Laraditz\Razorpay\Events\PaymentLinkPaid;
use Laraditz\Razorpay\Events\OrderPaid;
use Laraditz\Razorpay\Events\RefundProcessed;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;

protected $listen = [
    PaymentLinkPaid::class => [
        FulfillOrder::class,
    ],
    OrderPaid::class => [
        FulfillOrder::class,
    ],
    RefundProcessed::class => [
        NotifyCustomerOfRefund::class,
    ],
    RazorpayWebhookReceived::class => [
        LogAllRazorpayActivity::class,
    ],
];
```

### Example listener

```php
namespace App\Listeners;

use App\Models\Order; // your own application model, not this package's
use Laraditz\Razorpay\Events\PaymentLinkPaid;

class FulfillOrder
{
    public function handle(PaymentLinkPaid $event): void
    {
        $paymentLink = $event->paymentLink;

        if ($paymentLink === null) {
            return; // no local record matched — nothing to fulfill
        }

        // RazorpayWebhookReceived (and its sync listener) fires before this
        // typed event, so $paymentLink->status is already PaymentLinkStatus::Paid
        // here — no need to set or check any status yourself.
        // $paymentLink->reference_id is whatever you passed as reference_id on create()
        $order = Order::where('reference_id', $paymentLink->reference_id)->first();
        $order?->update(['fulfilled_at' => now()]);
    }
}
```

### Example: notify on payment capture

```php
namespace App\Listeners;

use Laraditz\Razorpay\Events\PaymentCaptured;

class SendPaymentReceipt
{
    public function handle(PaymentCaptured $event): void
    {
        if ($event->payment === null) {
            return;
        }

        Mail::to($event->payment->email)->send(new PaymentReceiptMail($event->payment));
    }
}
```

## Idempotency

Redelivered webhooks (Razorpay retries on a non-2xx response, or you can manually redeliver from the dashboard) are handled safely:

- Order/Payment Link/Refund sync listeners check the local record's current state before writing — a redelivery that would produce the same state is a no-op.
- Payment/Settlement sync uses `updateOrCreate()`, which is naturally idempotent regardless of delivery order.

## Auditing

Every signature-verified webhook is optionally recorded for later audit, separately from how signature *failures* are surfaced. See [Logging](logging.md).

## Related Documentation

- [Payment Links](payment-links.md)
- [Orders](orders.md)
- [Payments](payments.md)
- [Refunds](refunds.md)
- [Settlements](settlements.md)
- [Logging](logging.md)
