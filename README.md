# Laravel Razorpay

A Laravel wrapper package for the [Razorpay](https://razorpay.com) API. Built directly on Laravel's HTTP client (no `razorpay/razorpay-php` SDK dependency), it provides a fluent facade for Payment Links, Orders, and Refunds — with database persistence and webhook-driven, event-based sync out of the box.

## Features

- 🔗 **Payment Links** — create, fetch, update, cancel, list, resend notification
- 🧾 **Orders** — create, fetch, list, update, fetch payments for an order, plus Checkout payment-signature verification
- 💸 **Refunds** — create, fetch, list (account-wide and per-payment), update
- 🗄️ Local database persistence for every resource (`PaymentLink`, `Order`, `Refund`)
- 📜 **API request/response logging** — every outbound call recorded, with customer PII redacted and configurable retention
- 🔔 Automatic webhook handling with HMAC-SHA256 signature verification
- 🔁 Idempotent webhook sync — local records stay correct even if Razorpay retries a delivery
- 🎯 Generic + typed events for every webhook-driven state change
- 📦 Uses Laravel's HTTP client only — no Guzzle, no vendor SDK
- 🛡️ Type-safe with PHP 8.2+ backed enums
- 🚫 Package-owned exception hierarchy — never leaks raw HTTP or vendor exceptions

## Requirements

- PHP 8.2+ (Laravel 13.x itself requires PHP 8.3+)
- Laravel 10.x, 11.x, 12.x, or 13.x

## Installation

Install the package via composer:

```bash
composer require laraditz/razorpay
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag=razorpay-config
php artisan vendor:publish --tag=razorpay-migrations
```

Run the migrations:

```bash
php artisan migrate
```

Add your Razorpay credentials to `.env`:

```env
RAZORPAY_KEY_ID=rzp_test_your_key_id
RAZORPAY_KEY_SECRET=your_key_secret
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret
RAZORPAY_CURRENCY=MYR
```

## Configuration

`config/razorpay.php`:

```php
return [
    'key_id' => env('RAZORPAY_KEY_ID'),
    'key_secret' => env('RAZORPAY_KEY_SECRET'),
    'base_url' => env('RAZORPAY_BASE_URL', 'https://api.razorpay.com/v1'),
    'default_currency' => env('RAZORPAY_CURRENCY', 'MYR'),
    'timeout' => env('RAZORPAY_TIMEOUT', 30),
    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    'webhook_path' => env('RAZORPAY_WEBHOOK_PATH', '/razorpay/webhook'),
    'log_api_calls' => env('RAZORPAY_LOG_API_CALLS', true),
    'api_log_retention_days' => env('RAZORPAY_API_LOG_RETENTION_DAYS', 30),
];
```

## Usage

### Payment Links

```php
use Laraditz\Razorpay\Facades\Razorpay;

// Create a payment link — persists a local PaymentLink record automatically
$link = Razorpay::paymentLink()->create([
    'amount' => 50000, // smallest currency subunit
    'currency' => 'MYR',
    'description' => 'Payment for Order #123',
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'contact' => '+60123456789',
    ],
    'notify' => ['sms' => true, 'email' => true],
    'reference_id' => 'ORDER-123',
]);

return redirect($link['short_url']);

// Fetch the current state from Razorpay
$link = Razorpay::paymentLink()->fetch('plink_ExjpAUN3gVHrPJ');

// List payment links (any Razorpay filter: payment_id, reference_id, upi_link, count, skip)
$links = Razorpay::paymentLink()->all(['count' => 20]);

// Update notes/reference_id/etc.
Razorpay::paymentLink()->update('plink_ExjpAUN3gVHrPJ', [
    'reference_id' => 'ORDER-123-B',
]);

// Cancel — also updates the local record's status immediately, without waiting for the webhook
Razorpay::paymentLink()->cancel('plink_ExjpAUN3gVHrPJ');

// Resend the payment link notification
Razorpay::paymentLink()->notifyBy('plink_ExjpAUN3gVHrPJ', 'sms'); // or 'email'
```

### Orders

```php
use Laraditz\Razorpay\Facades\Razorpay;

// Create an order — persists a local Order record automatically
$order = Razorpay::order()->create([
    'amount' => 50000,
    'currency' => 'MYR',
    'receipt' => 'receipt_1',
]);

// Pass $order['id'] to Razorpay Checkout.js on the frontend

$order = Razorpay::order()->fetch('order_EKwxwAgItmmXdp');

$orders = Razorpay::order()->all(['count' => 20]);

Razorpay::order()->update('order_EKwxwAgItmmXdp', [
    'notes' => ['internal_ref' => 'abc123'],
]);

// List every payment attempt made against an order (useful for reconciling retries)
$payments = Razorpay::order()->fetchPayments('order_EKwxwAgItmmXdp');
```

#### Verifying a Checkout.js payment signature

After Checkout.js completes, Razorpay returns `razorpay_order_id`, `razorpay_payment_id`, and `razorpay_signature` to your callback. Verify them server-side before trusting the payment:

```php
use Laraditz\Razorpay\Facades\Razorpay;

$isValid = Razorpay::order()->verifyPaymentSignature(
    $request->input('razorpay_order_id'),
    $request->input('razorpay_payment_id'),
    $request->input('razorpay_signature'),
);

if (! $isValid) {
    abort(400, 'Invalid payment signature.');
}
```

### Refunds

```php
use Laraditz\Razorpay\Facades\Razorpay;

// Full refund — persists a local Refund record automatically
$refund = Razorpay::refund()->create('pay_29QQoUBi66xm2f');

// Partial refund with options
$refund = Razorpay::refund()->create('pay_29QQoUBi66xm2f', [
    'amount' => 10000,
    'speed' => 'optimum',
    'notes' => ['reason' => 'requested by customer'],
]);

$refund = Razorpay::refund()->fetch('rfnd_EL845GtTZl41Xn');

// All refunds, account-wide
$refunds = Razorpay::refund()->all(['count' => 20]);

// All refunds for a specific payment (useful when multiple partial refunds exist)
$refunds = Razorpay::refund()->forPayment('pay_29QQoUBi66xm2f');

Razorpay::refund()->update('rfnd_EL845GtTZl41Xn', [
    'notes' => ['internal_ref' => 'abc123'],
]);
```

### Querying local records

Every `create()` call persists a local Eloquent record, kept in sync automatically as webhooks arrive — no manual polling required:

```php
use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Models\PaymentLink;
use Laraditz\Razorpay\Models\Order;
use Laraditz\Razorpay\Models\Refund;

$paidLinks = PaymentLink::where('status', PaymentLinkStatus::Paid)->get();
$order = Order::where('razorpay_id', 'order_EKwxwAgItmmXdp')->first();
$refunds = Refund::where('payment_id', 'pay_29QQoUBi66xm2f')->get();

if ($order->status->isPaid()) {
    // ...
}
```

### Error Handling

Every non-2xx API response is caught and rethrown as a package-owned exception — you never need to catch raw HTTP client exceptions:

```php
use Laraditz\Razorpay\Exceptions\AuthenticationException; // 401
use Laraditz\Razorpay\Exceptions\ValidationException;     // 400, carries field errors via getErrors()
use Laraditz\Razorpay\Exceptions\ApiException;             // any other 4xx/5xx, carries the full body via getResponse()

try {
    Razorpay::paymentLink()->create(['amount' => 50000]);
} catch (ValidationException $e) {
    logger()->warning('Razorpay validation failed', $e->getErrors());
} catch (ApiException $e) {
    logger()->error('Razorpay API error', $e->getResponse());
}
```

## Webhooks

A webhook route is registered automatically at `POST /razorpay/webhook` (configurable via `RAZORPAY_WEBHOOK_PATH`). It is **not** part of Laravel's `web` middleware group, so it is never subject to CSRF verification — the `X-Razorpay-Signature` header (HMAC-SHA256 over the raw request body) is the sole authentication boundary.

In your Razorpay Dashboard, point the webhook URL to `https://yourapp.com/razorpay/webhook` and set the same secret as `RAZORPAY_WEBHOOK_SECRET`.

### What happens automatically

1. The signature is verified — an invalid or missing signature is rejected with `401` before any payload is processed.
2. A generic `RazorpayWebhookReceived` event fires for **every** verified webhook, regardless of type — nothing is ever silently dropped, even for event types this package doesn't have typed handling for yet.
3. Typed events fire for the events this package currently understands, each matched to its local record where possible:

| Event | Razorpay event | Local model kept in sync |
|---|---|---|
| `PaymentLinkPaid` | `payment_link.paid` | `PaymentLink` |
| `PaymentCaptured` | `payment.captured` | `PaymentLink` (via `order_id`) |
| `PaymentFailed` | `payment.failed` | `PaymentLink` (via `order_id`) |
| `OrderPaid` | `order.paid` | `Order` |
| `RefundCreated` | `refund.created` | `Refund` |
| `RefundProcessed` | `refund.processed` | `Refund` |
| `RefundFailed` | `refund.failed` | `Refund` |

If no local record matches (e.g. the resource wasn't created through this package), typed events still dispatch with a `null` model instead of throwing — always check for `null` before using it.

> **Note:** Razorpay's `refund.created` webhook can arrive with a payload that already reports `status: "processed"` (e.g. instant refunds). The `Refund` sync logic reads the actual status from the payload rather than assuming `pending` from the event name, so your local record always ends up correct regardless of which event delivered it.

### Listening to events

```php
use Laraditz\Razorpay\Events\PaymentLinkPaid;
use Laraditz\Razorpay\Events\OrderPaid;
use Laraditz\Razorpay\Events\RefundProcessed;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;

// In your EventServiceProvider
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

## API Request/Response Logging

Every outbound call `RazorpayClient` makes — across Payment Links, Orders, and Refunds — is recorded in the `razorpay_api_logs` table via the `ApiLog` model, whether it succeeds, fails with a non-2xx response, or fails to connect at all.

```php
use Laraditz\Razorpay\Models\ApiLog;

$log = ApiLog::latest()->first();

$log->method;            // 'POST'
$log->endpoint;          // '/payment_links'
$log->reference_id;      // 'plink_ExjpAUN3gVHrPJ' — best-effort, from the response's `id`
$log->request_payload;   // array, PII-redacted
$log->response_payload;  // array, PII-redacted (null if no response was received)
$log->http_status;       // 200 (null on a connection failure)
$log->duration_ms;       // 842
```

### What's redacted

`customer.name`, `customer.email`, and `customer.contact` — the only structured PII fields Razorpay's API exposes, present on Payment Link request/response bodies (including inside each item of an `all()` list response) — are replaced with a keyed hash before storage:

```php
// Original: 'customer' => ['name' => 'John Doe', 'email' => 'john@example.com', ...]
// Stored:   'customer' => ['name' => '[redacted:9f86d0...]', 'email' => '[redacted:e3b0c4...]', ...]
```

The hash is `hash_hmac('sha256', $value, config('app.key'))` — keyed, not a plain hash, so it can't be trivially reversed via a precomputed lookup for common names/emails. `notes` (free-text, present on all resources) is **not** scanned or redacted — there's no reliable way to pattern-match arbitrary merchant-authored text. HTTP headers (including the `Authorization` header carrying your `key_secret`) are **never** logged at all.

### Disabling logging

```env
RAZORPAY_LOG_API_CALLS=false
```

### Retention

Rows older than `RAZORPAY_API_LOG_RETENTION_DAYS` (default 30) are eligible for deletion via Laravel's built-in pruning — no custom command needed. If your app already schedules `model:prune`, `ApiLog` is picked up automatically:

```php
// routes/console.php or app/Console/Kernel.php
Schedule::command('model:prune')->daily();
```

## Testing

```bash
composer test
```

The test suite uses `Http::fake()`/`Event::fake()` throughout — no real network access or live Razorpay credentials are required.

## Out of Scope

- Checkout.js widget/JS integration itself — this package covers the server-side half (create an Order, verify the returned signature); rendering the Checkout modal is your application's responsibility
- Subscriptions, Payment Pages
- Local persistence of individual Payments — only Payment Links, Orders, and Refunds are tracked locally

## Security

If you discover any security related issues, please email raditzfarhan@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT).
