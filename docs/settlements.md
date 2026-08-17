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

### `fetchRecon(int $year, int $month, ?int $day = null, ?int $count = null, ?int $skip = null): array`

**Official API:** `GET /settlements/recon/combined`

Transaction-level recon report — every payment, refund, transfer, and adjustment settled in the given period, each tagged with which settlement batch and UTR it belongs to. Unlike `fetch()`/`all()`, which operate at the settlement-batch level, this returns individual line items, useful for answering "was this specific payment settled, and in which batch?"

`year`/`month` are required; `day` narrows to a single date. `count`/`skip` paginate a single page at a time — this method does not auto-paginate, so a period with more than 1000 items needs multiple calls.

```php
use Laraditz\Razorpay\Facades\Razorpay;

$recon = Razorpay::settlement()->fetchRecon(2026, 8);

// Narrow to a single day
$recon = Razorpay::settlement()->fetchRecon(2026, 8, 11);

// Page through a busy month
$recon = Razorpay::settlement()->fetchRecon(2026, 8, count: 500, skip: 500);
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

`RazorpaySettlementTransaction` (`razorpay_settlement_transactions` table), populated only by `fetchRecon()`, one row per transaction line item. Uniquely keyed on `entity_id` + `type` (not `razorpay_id` — a single recon call can return payments, refunds, transfers, and adjustments, and `entity_id` alone isn't guaranteed unique across those categories). `settlement_id` links back to `RazorpaySettlement.razorpay_id` via a nullable, non-enforced foreign key — a recon item can reference a settlement batch that hasn't been fetched/synced locally yet.

```php
$settlement->transactions;      // all RazorpaySettlementTransaction rows for this settlement
$transaction->settlement;       // the RazorpaySettlement this line item belongs to (null if not yet synced)
```

## Related Documentation

- [Payments](payments.md)
- [Webhooks](webhooks.md)
