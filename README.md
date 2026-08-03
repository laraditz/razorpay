# Laravel Curlec by Razorpay

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraditz/razorpay.svg?style=flat-square)](https://packagist.org/packages/laraditz/razorpay)
[![Total Downloads](https://img.shields.io/packagist/dt/laraditz/razorpay.svg?style=flat-square)](https://packagist.org/packages/laraditz/razorpay)
[![License](https://img.shields.io/packagist/l/laraditz/razorpay?style=flat-square)](./LICENSE.md)
![GitHub Actions](https://github.com/laraditz/razorpay/actions/workflows/main.yml/badge.svg)

A Laravel wrapper package for the [Razorpay Curlec](https://curlec.com) API. Built directly on Laravel's HTTP client, it provides a fluent facade for Payment Links, Orders, Payments, Refunds, and Settlements with database persistence and webhook-driven, event-based sync out of the box.

## Features

- 🔗 **Payment Links** — create, fetch, update, cancel, list, resend notification
- 🧾 **Orders** — create, fetch, list, update, fetch payments for an order, plus Checkout payment-signature verification
- 💳 **Payments** — fetch, capture, update, list — synced locally from both the API response and the `payment.authorized`/`captured`/`failed` webhooks
- 💸 **Refunds** — create, fetch, list (account-wide and per-payment), update
- 🏦 **Settlements** — fetch, list — synced locally from both the API response and the `settlement.processed` webhook
- 🗄️ Local database persistence for every resource (`RazorpayPaymentLink`, `RazorpayOrder`, `RazorpayPayment`, `RazorpayRefund`, `RazorpaySettlement`), each a `payment_id`/`razorpay_id` join away from the others
- 📜 **API request/response logging** — every outbound call recorded, with customer PII redacted and configurable retention
- 🕵️ **Webhook audit log** — every signature-valid inbound webhook recorded for later audit, separately from signature-failure diagnostics in your app's log channel
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
    'log_webhook_calls' => env('RAZORPAY_LOG_WEBHOOK_CALLS', true),
    'webhook_log_retention_days' => env('RAZORPAY_WEBHOOK_LOG_RETENTION_DAYS', 30),
];
```

## Usage

Every service is accessed through the `Razorpay` facade. Full method reference, parameters, and more examples for each are in [`/docs`](docs) — linked below and in the [Documentation](#documentation) section.

### Payment Links

```php
use Laraditz\Razorpay\Facades\Razorpay;

$link = Razorpay::paymentLink()->create([
    'amount' => 50000, // smallest currency subunit
    'currency' => 'MYR',
    'customer' => ['name' => 'John Doe', 'email' => 'john@example.com'],
    'reference_id' => 'ORDER-123',
]);

return redirect($link['short_url']);
```

→ [Full documentation](docs/payment-links.md)

### Orders

```php
use Laraditz\Razorpay\Facades\Razorpay;

$order = Razorpay::order()->create(['amount' => 50000, 'currency' => 'MYR']);

// Pass $order['id'] to Checkout.js, then verify the signature it returns:
$isValid = Razorpay::order()->verifyPaymentSignature(
    $request->input('razorpay_order_id'),
    $request->input('razorpay_payment_id'),
    $request->input('razorpay_signature'),
);
```

→ [Full documentation](docs/orders.md)

### Payments

```php
use Laraditz\Razorpay\Facades\Razorpay;

$payment = Razorpay::payment()->fetch('pay_29QQoUBi66xm2f');
$payment = Razorpay::payment()->capture('pay_29QQoUBi66xm2f', ['amount' => 50000, 'currency' => 'MYR']);
```

→ [Full documentation](docs/payments.md)

### Refunds

```php
use Laraditz\Razorpay\Facades\Razorpay;

$refund = Razorpay::refund()->create('pay_29QQoUBi66xm2f', ['amount' => 10000]);
```

→ [Full documentation](docs/refunds.md)

### Settlements

```php
use Laraditz\Razorpay\Facades\Razorpay;

$settlements = Razorpay::settlement()->all(['count' => 20]);
```

→ [Full documentation](docs/settlements.md)

### Querying local records

Every `create()` call (and, for Payments/Settlements, every `fetch()`/`capture()`/`update()`/`all()` call too) persists a local Eloquent record, kept in sync automatically as webhooks arrive — no manual polling required:

```php
use Laraditz\Razorpay\Models\RazorpayOrder;

$order = RazorpayOrder::where('razorpay_id', 'order_EKwxwAgItmmXdp')->first();

if ($order->status->isPaid()) {
    // ...
}

$order->payment; // the RazorpayPayment that settled it, via the payment_id column
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

A webhook route is registered automatically at `POST /razorpay/webhook` (configurable via `RAZORPAY_WEBHOOK_PATH`) — never part of Laravel's `web` middleware group, so the `X-Razorpay-Signature` header is the sole authentication boundary. Point your Razorpay Dashboard's webhook URL at it and set `RAZORPAY_WEBHOOK_SECRET`.

Typed events fire for the events this package understands (`PaymentLinkPaid`, `PaymentAuthorized`, `PaymentCaptured`, `PaymentFailed`, `OrderPaid`, `RefundCreated`/`Processed`/`Failed`, `SettlementProcessed`), each keeping the matching local record in sync — plus a generic `RazorpayWebhookReceived` event for every verified delivery, regardless of type.

```php
class FulfillOrder
{
    public function handle(\Laraditz\Razorpay\Events\PaymentLinkPaid $event): void
    {
        if ($event->paymentLink === null) {
            return;
        }

        // $event->paymentLink->status is already PaymentLinkStatus::Paid here
    }
}
```

→ [Full documentation](docs/webhooks.md) — event reference, listener setup, idempotency notes

## Logging

Outbound API calls and inbound webhook deliveries can each be optionally recorded for troubleshooting/audit, independently toggled and retained:

```env
RAZORPAY_LOG_API_CALLS=false
RAZORPAY_LOG_WEBHOOK_CALLS=false
```

→ [Full documentation](docs/logging.md)

## Documentation

For detailed documentation on each service, please refer to:

- **[Payment Links](docs/payment-links.md)** — create, fetch, update, cancel, list, resend notification
- **[Orders](docs/orders.md)** — create, fetch, list, update, fetch payments for an order, Checkout signature verification
- **[Payments](docs/payments.md)** — fetch, capture, update, list
- **[Refunds](docs/refunds.md)** — full/partial refunds, account-wide and per-payment listing
- **[Settlements](docs/settlements.md)** — fetch, list, reconciliation
- **[Webhooks](docs/webhooks.md)** — event reference, listener setup, idempotency
- **[Logging](docs/logging.md)** — API request/response logging and webhook audit log

## Testing

```bash
composer test
```

The test suite uses `Http::fake()`/`Event::fake()` throughout — no real network access or live Razorpay credentials are required.

## Security

If you discover any security related issues, please email raditzfarhan@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT).
