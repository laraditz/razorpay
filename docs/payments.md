# Payments

Payments aren't created through this package — Razorpay creates them when a customer pays via Checkout, a Payment Link, or an Order. `PaymentService` lets you fetch, capture, update, and list them, and every call keeps a local `RazorpayPayment` record in sync, since a `fetch()`/`capture()` is often the first time a payment becomes known locally at all.

**Official Documentation:** https://razorpay.com/docs/api/payments/

## Available Methods

### `fetch(string $id): array`

**Official API:** `GET /payments/{id}`

```php
use Laraditz\Razorpay\Facades\Razorpay;

$payment = Razorpay::payment()->fetch('pay_29QQoUBi66xm2f');
```

### `capture(string $id, array $data): array`

Capture an authorized-but-not-yet-captured payment — relevant if auto-capture is disabled on your account.

**Official API:** `POST /payments/{id}/capture`

**Parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `amount` | int | Yes | Amount to capture, smallest currency subunit |
| `currency` | string | Yes | Must match the payment's currency |

```php
$payment = Razorpay::payment()->capture('pay_29QQoUBi66xm2f', [
    'amount' => 50000,
    'currency' => 'MYR',
]);
```

### `update(string $id, array $data): array`

Update a payment's `notes`.

**Official API:** `PATCH /payments/{id}`

```php
Razorpay::payment()->update('pay_29QQoUBi66xm2f', [
    'notes' => ['internal_ref' => 'abc123'],
]);
```

### `all(array $query = []): array`

**Official API:** `GET /payments`

**Parameters:** any Razorpay filter — `from`, `to`, `count`, `skip`.

```php
$payments = Razorpay::payment()->all(['count' => 20]);
```

## Usage Examples

### Capture a manually-authorized payment

```php
use Laraditz\Razorpay\Facades\Razorpay;
use Laraditz\Razorpay\Models\RazorpayPayment;

$payment = RazorpayPayment::where('razorpay_id', $paymentId)->firstOrFail();

if ($payment->status === \Laraditz\Razorpay\Enums\PaymentStatus::Authorized) {
    Razorpay::payment()->capture($payment->razorpay_id, [
        'amount' => $payment->amount,
        'currency' => $payment->currency,
    ]);
}
```

### Trace a payment back to the order/link that produced it

```php
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;

$order = RazorpayOrder::where('payment_id', $payment->razorpay_id)->first();
$link = RazorpayPaymentLink::where('payment_id', $payment->razorpay_id)->first();
```

### Reconcile against refunds

```php
use Laraditz\Razorpay\Models\RazorpayRefund;

$refunds = RazorpayRefund::where('payment_id', $payment->razorpay_id)->get();
$refundedTotal = $refunds->sum('amount');
```

## Status Values

`Laraditz\Razorpay\Enums\PaymentStatus`

| Status | Description |
|---|---|
| `created` | Payment initiated, not yet authorized |
| `authorized` | Funds authorized, not yet captured (only meaningful if auto-capture is off) |
| `captured` | Funds captured — this is the "paid" state |
| `refunded` | Fully refunded |
| `failed` | Payment attempt failed |

## Local Record

`RazorpayPayment` (`razorpay_payments` table), populated by `PaymentService`'s methods and by the `payment.authorized`/`payment.captured`/`payment.failed` webhooks — both paths share the same `RazorpayPayment::syncFromResponse()` method, since a webhook's payment payload and an API fetch response are the same shape. Key columns: `razorpay_id`, `order_id`, `status`, `method`, `amount`, `amount_refunded`, `currency`, `captured`, `description`, `email`, `contact`, `notes`, `fee`, `tax`, `error_code`, `error_description`, `raw_response`.

`RazorpayOrder`, `RazorpayPaymentLink`, and `RazorpayRefund` each expose a `payment()` relationship resolving back to the `RazorpayPayment` that settled them, via `payment_id`.

## Related Documentation

- [Orders](orders.md)
- [Refunds](refunds.md)
- [Webhooks](webhooks.md)
