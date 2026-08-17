# Payment Links

The Payment Links API lets you generate a shareable checkout URL — send it via SMS, email, or embed it directly — without building your own payment form.

**Official Documentation:** https://curlec.com/docs/api/payments/payment-links/

## Available Methods

### `create(array $data): array`

Create a payment link. Persists a local `RazorpayPaymentLink` record automatically.

**Official API:** `POST /payment_links`

**Parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `amount` | int | Yes | Amount in the smallest currency subunit (e.g. cents/sen) |
| `currency` | string | No | Defaults to `config('razorpay.default_currency')` |
| `description` | string | No | Shown to the customer on the payment page |
| `customer` | array | No | `name`, `email`, `contact` |
| `notify` | array | No | `sms`, `email` — whether Razorpay sends its own notification |
| `reference_id` | string | No | Your own identifier — the recommended way to tie a link back to your app's data |
| `notes` | array | No | Free-form key/value metadata |
| `expire_by` | int | No | Unix timestamp the link expires at |
| `callback_url` / `callback_method` | string | No | Where Razorpay redirects the customer after payment |

**Example:**

```php
use Laraditz\Razorpay\Facades\Razorpay;

$link = Razorpay::paymentLink()->create([
    'amount' => 50000,
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
```

### `fetch(string $id): array`

Fetch a payment link's current state directly from Razorpay.

**Official API:** `GET /payment_links/{id}`

```php
$link = Razorpay::paymentLink()->fetch('plink_ExjpAUN3gVHrPJ');
```

### `all(array $query = []): array`

List payment links.

**Official API:** `GET /payment_links`

**Parameters:** any Razorpay filter — `payment_id`, `reference_id`, `upi_link`, `count`, `skip`.

```php
$links = Razorpay::paymentLink()->all(['count' => 20]);
```

### `update(string $id, array $data): array`

Update mutable fields (`notes`, `reference_id`, `expire_by`, `reminder_enable`) on a payment link.

**Official API:** `PATCH /payment_links/{id}`

```php
Razorpay::paymentLink()->update('plink_ExjpAUN3gVHrPJ', [
    'reference_id' => 'ORDER-123-B',
]);
```

### `cancel(string $id): array`

Cancel a payment link. Also updates the local record's `status`/`cancelled_at` immediately, without waiting for the webhook.

**Official API:** `POST /payment_links/{id}/cancel`

```php
Razorpay::paymentLink()->cancel('plink_ExjpAUN3gVHrPJ');
```

### `notifyBy(string $id, string $medium): array`

Resend the payment link notification.

**Official API:** `POST /payment_links/{id}/notify_by/{medium}`

**Parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `medium` | string | Yes | `'sms'` or `'email'` |

```php
Razorpay::paymentLink()->notifyBy('plink_ExjpAUN3gVHrPJ', 'sms');
```

## Attaching to Your Own Models

`create()` accepts an optional fluent `for()` call to link the payment link to any model in your app via a polymorphic `subject_id`/`subject_type` pair — no extra column or trait needed on your own model:

```php
use Laraditz\Razorpay\Facades\Razorpay;

$link = Razorpay::paymentLink()->for($invoice)->create([
    'amount' => 50000,
    'currency' => 'MYR',
]);

// Later:
$link = RazorpayPaymentLink::where('razorpay_id', $link['id'])->first();
$link->subject; // $invoice, resolved via the polymorphic relationship
```

`for()` is entirely optional — omitting it (or passing `null`) leaves `subject_id`/`subject_type` as `null`, exactly like before this existed. If your app uses `Relation::morphMap()`, it's respected automatically.

If you want to query the other direction (e.g. `$invoice->razorpayPaymentLinks`), define that relationship yourself on your own model — this package only provides the `RazorpayPaymentLink` side:

```php
// App\Models\Invoice
public function razorpayPaymentLinks(): MorphMany
{
    return $this->morphMany(RazorpayPaymentLink::class, 'subject');
}
```

## Usage Examples

### Redirect customer after checkout creation

```php
use Laraditz\Razorpay\Facades\Razorpay;

$link = Razorpay::paymentLink()->create([
    'amount' => $order->total_in_cents,
    'currency' => 'MYR',
    'reference_id' => $order->id,
    'notify' => ['sms' => true, 'email' => true],
]);

$order->update(['razorpay_payment_link_id' => $link['id']]);

return redirect($link['short_url']);
```

### Look up a link by your own reference

```php
use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;

$link = RazorpayPaymentLink::where('reference_id', $order->id)->first();

if ($link?->status === PaymentLinkStatus::Paid) {
    $order->markAsPaid();
}
```

### Cancel a stale link before regenerating one

```php
use Laraditz\Razorpay\Facades\Razorpay;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;

$existing = RazorpayPaymentLink::where('reference_id', $order->id)
    ->whereNotNull('id')
    ->first();

if ($existing && !$existing->status->isFinal()) {
    Razorpay::paymentLink()->cancel($existing->razorpay_id);
}

$link = Razorpay::paymentLink()->create([...]);
```

## Status Values

`Laraditz\Razorpay\Enums\PaymentLinkStatus`

| Status | Description |
|---|---|
| `created` | Link generated, awaiting payment |
| `partially_paid` | Partial payment received (if `accept_partial` was enabled) |
| `paid` | Fully paid |
| `expired` | Passed its `expire_by` time unpaid |
| `cancelled` | Cancelled via `cancel()` |

`$link->status->isPaid()` and `$link->status->isFinal()` are available as convenience checks.

## Local Record

Every `create()` call, and every status transition delivered by webhook, is reflected on `RazorpayPaymentLink` (`razorpay_payment_links` table) — no `$table` override, standard Eloquent model. Key columns: `razorpay_id`, `order_id`, `payment_id`, `subject_id`/`subject_type`, `status`, `amount`, `amount_paid`, `currency`, `reference_id`, `customer_name`/`customer_email`/`customer_contact`, `notes`, `short_url`, `raw_response`, `expire_by`, `paid_at`, `cancelled_at`, `expired_at`. Soft-deletable.

`$link->payment` resolves the matching `RazorpayPayment` via `payment_id` once one exists.

## Related Documentation

- [Orders](orders.md)
- [Payments](payments.md)
- [Webhooks](webhooks.md)
