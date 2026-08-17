# Refunds

The Refunds API lets you fully or partially refund a captured payment, and look up refund history — account-wide or scoped to a single payment.

**Official Documentation:** https://curlec.com/docs/api/refunds/

## Available Methods

### `create(string $paymentId, array $data = []): array`

Create a refund against a payment. Persists a local `RazorpayRefund` record automatically.

**Official API:** `POST /payments/{payment_id}/refund`

**Parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `paymentId` | string | Yes | The payment to refund |
| `amount` | int | No | Omit for a full refund; smallest currency subunit for a partial one |
| `speed` | string | No | `'normal'` or `'optimum'` |
| `notes` | array | No | Free-form key/value metadata |
| `receipt` | string | No | Your own receipt reference |

**Example:**

```php
use Laraditz\Razorpay\Facades\Razorpay;

// Full refund
$refund = Razorpay::refund()->create('pay_29QQoUBi66xm2f');

// Partial refund with options
$refund = Razorpay::refund()->create('pay_29QQoUBi66xm2f', [
    'amount' => 10000,
    'speed' => 'optimum',
    'notes' => ['reason' => 'requested by customer'],
]);
```

### `fetch(string $id): array`

**Official API:** `GET /refunds/{id}`

```php
$refund = Razorpay::refund()->fetch('rfnd_EL845GtTZl41Xn');
```

### `all(array $query = []): array`

List refunds, account-wide.

**Official API:** `GET /refunds`

```php
$refunds = Razorpay::refund()->all(['count' => 20]);
```

### `forPayment(string $paymentId, array $query = []): array`

List refunds for a specific payment — useful when multiple partial refunds exist.

**Official API:** `GET /payments/{payment_id}/refunds`

```php
$refunds = Razorpay::refund()->forPayment('pay_29QQoUBi66xm2f');
```

### `update(string $id, array $data): array`

Update a refund's `notes`.

**Official API:** `PATCH /refunds/{id}`

```php
Razorpay::refund()->update('rfnd_EL845GtTZl41Xn', [
    'notes' => ['internal_ref' => 'abc123'],
]);
```

## Usage Examples

### Refund a payment and notify the customer once processed

```php
use Laraditz\Razorpay\Events\RefundProcessed;
use Laraditz\Razorpay\Facades\Razorpay;

Razorpay::refund()->create($payment->razorpay_id, [
    'amount' => $refundAmount,
    'notes' => ['reason' => $request->input('reason')],
]);

// Elsewhere, in a listener:
class NotifyCustomerOfRefund
{
    public function handle(RefundProcessed $event): void
    {
        if ($event->refund === null) {
            return;
        }

        Mail::to($event->refund->email)->send(new RefundProcessedMail($event->refund));
    }
}
```

### Check total refunded against a payment before allowing another partial refund

```php
use Laraditz\Razorpay\Models\RazorpayRefund;

$alreadyRefunded = RazorpayRefund::where('payment_id', $payment->razorpay_id)
    ->whereIn('status', [\Laraditz\Razorpay\Enums\RefundStatus::Processed])
    ->sum('amount');

if ($alreadyRefunded + $requestedAmount > $payment->amount) {
    abort(422, 'Refund exceeds remaining payment amount.');
}
```

## Status Values

`Laraditz\Razorpay\Enums\RefundStatus`

| Status | Description |
|---|---|
| `pending` | Refund initiated |
| `processed` | Refund completed |
| `failed` | Refund failed |

`$refund->status->isProcessed()` and `$refund->status->isFinal()` are available as convenience checks.

> Razorpay's `refund.created` webhook can arrive with a payload that already reports `status: "processed"` (e.g. instant refunds). This package's sync logic reads the actual status from the payload rather than assuming `pending` from the event name, so your local record is always correct regardless of which event delivered it.

## Local Record

`RazorpayRefund` (`razorpay_refunds` table). Key columns: `razorpay_id`, `payment_id`, `status`, `amount`, `currency`, `notes`, `receipt`, `speed_requested`, `speed_processed`, `raw_response`, `processed_at`, `failed_at`. Soft-deletable.

`$refund->payment` resolves the matching `RazorpayPayment` via `payment_id`.

## Related Documentation

- [Payments](payments.md)
- [Webhooks](webhooks.md)
