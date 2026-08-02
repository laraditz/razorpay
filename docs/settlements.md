# Settlements

Settlements are Razorpay's payouts to your bank account, generated on Razorpay's own schedule — not something you create through the API in normal use, just look up for reconciliation.

**Official Documentation:** https://razorpay.com/docs/api/settlements/

## Available Methods

### `fetch(string $id): array`

**Official API:** `GET /settlements/{id}`

```php
use Laraditz\Razorpay\Facades\Razorpay;

$settlement = Razorpay::settlement()->fetch('setl_ExjpAUN3gVHrPJ');
```

### `all(array $query = []): array`

**Official API:** `GET /settlements`

**Parameters:** any Razorpay filter — `from`, `to`, `count`, `skip`.

```php
$settlements = Razorpay::settlement()->all(['count' => 20]);
```

## Usage Examples

### Reconcile a bank deposit against Razorpay's settlement record

```php
use Laraditz\Razorpay\Facades\Razorpay;
use Laraditz\Razorpay\Models\RazorpaySettlement;

// Bank statement shows a UTR — look it up locally first
$settlement = RazorpaySettlement::where('utr', $bankStatementUtr)->first();

// Not seen yet? Pull the latest batch and try again
if (! $settlement) {
    Razorpay::settlement()->all(['count' => 50]);
    $settlement = RazorpaySettlement::where('utr', $bankStatementUtr)->first();
}
```

### Sum settled amounts for a date range

```php
use Laraditz\Razorpay\Enums\SettlementStatus;
use Laraditz\Razorpay\Models\RazorpaySettlement;

$total = RazorpaySettlement::where('status', SettlementStatus::Processed)
    ->whereBetween('settled_at', [$from, $to])
    ->sum('amount');
```

## Status Values

`Laraditz\Razorpay\Enums\SettlementStatus`

| Status | Description |
|---|---|
| `created` | Settlement record created |
| `processed` | Funds transferred — `settled_at` is set at this point |
| `failed` | Settlement failed |

## Local Record

`RazorpaySettlement` (`razorpay_settlements` table), populated by `SettlementService`'s methods and by the `settlement.processed` webhook — both paths share the same `RazorpaySettlement::syncFromResponse()` method. Key columns: `razorpay_id`, `amount`, `fees`, `tax`, `utr`, `status`, `settled_at`, `raw_response`. `settled_at` is only ever set once `status` is `processed`.

## Related Documentation

- [Payments](payments.md)
- [Webhooks](webhooks.md)
