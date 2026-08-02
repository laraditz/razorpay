# Orders

The Orders API is the standard entry point for Razorpay Checkout.js — create an order server-side, hand its `id` to the frontend widget, then verify the payment signature it returns.

**Official Documentation:** https://razorpay.com/docs/api/orders/

## Available Methods

### `create(array $data): array`

Create an order. Persists a local `RazorpayOrder` record automatically.

**Official API:** `POST /orders`

**Parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `amount` | int | Yes | Amount in the smallest currency subunit |
| `currency` | string | No | Defaults to `config('razorpay.default_currency')` |
| `receipt` | string | No | Your own receipt/order reference |
| `notes` | array | No | Free-form key/value metadata |

**Example:**

```php
use Laraditz\Razorpay\Facades\Razorpay;

$order = Razorpay::order()->create([
    'amount' => 50000,
    'currency' => 'MYR',
    'receipt' => 'receipt_1',
]);

// Pass $order['id'] to Razorpay Checkout.js on the frontend
```

### `fetch(string $id): array`

**Official API:** `GET /orders/{id}`

```php
$order = Razorpay::order()->fetch('order_EKwxwAgItmmXdp');
```

### `all(array $query = []): array`

**Official API:** `GET /orders`

```php
$orders = Razorpay::order()->all(['count' => 20]);
```

### `update(string $id, array $data): array`

Update an order's `notes`.

**Official API:** `PATCH /orders/{id}`

```php
Razorpay::order()->update('order_EKwxwAgItmmXdp', [
    'notes' => ['internal_ref' => 'abc123'],
]);
```

### `fetchPayments(string $id): array`

List every payment attempt made against an order — useful for reconciling retries after a decline. Also bulk-syncs a local `RazorpayPayment` record for every item returned.

**Official API:** `GET /orders/{id}/payments`

```php
$payments = Razorpay::order()->fetchPayments('order_EKwxwAgItmmXdp');
```

### `verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool`

Verifies the `razorpay_order_id`/`razorpay_payment_id`/`razorpay_signature` trio Checkout.js returns to your callback. This is a local HMAC check — no API call is made.

```php
$isValid = Razorpay::order()->verifyPaymentSignature(
    $request->input('razorpay_order_id'),
    $request->input('razorpay_payment_id'),
    $request->input('razorpay_signature'),
);

if (! $isValid) {
    abort(400, 'Invalid payment signature.');
}
```

## Usage Examples

### Full Checkout.js server-side flow

```php
use Laraditz\Razorpay\Facades\Razorpay;

// 1. Create the order
$order = Razorpay::order()->create([
    'amount' => $cart->totalInCents(),
    'currency' => 'MYR',
    'receipt' => $cart->id,
]);

// 2. Return $order['id'] + your key_id to the frontend for Checkout.js

// 3. In the payment callback route:
$isValid = Razorpay::order()->verifyPaymentSignature(
    $request->input('razorpay_order_id'),
    $request->input('razorpay_payment_id'),
    $request->input('razorpay_signature'),
);

abort_unless($isValid, 400, 'Invalid payment signature.');

// The order.paid webhook (or a direct fetch) will mark the local
// RazorpayOrder as paid — no need to update status here yourself.
```

### Reconcile retried payment attempts

```php
use Laraditz\Razorpay\Facades\Razorpay;

$attempts = Razorpay::order()->fetchPayments($order->razorpay_id);

foreach ($attempts['items'] as $attempt) {
    logger()->info("Attempt {$attempt['id']}: {$attempt['status']}");
}

// Every attempt is now also queryable locally:
$order->payment; // the RazorpayPayment that actually settled the order, if any
```

## Status Values

`Laraditz\Razorpay\Enums\OrderStatus`

| Status | Description |
|---|---|
| `created` | Order created, no successful payment yet |
| `attempted` | At least one payment attempt was made |
| `paid` | Fully paid |

`$order->status->isPaid()` and `$order->status->isFinal()` are available as convenience checks.

## Local Record

`RazorpayOrder` (`razorpay_orders` table). Key columns: `razorpay_id`, `payment_id`, `status`, `amount`, `amount_paid`, `amount_due`, `currency`, `receipt`, `attempts`, `notes`, `raw_response`, `paid_at`. Soft-deletable.

`$order->payment` resolves the matching `RazorpayPayment` via `payment_id` once one exists — set only when the order actually transitions to paid, never for a failed/retried attempt.

## Related Documentation

- [Payments](payments.md)
- [Payment Links](payment-links.md)
- [Webhooks](webhooks.md)
