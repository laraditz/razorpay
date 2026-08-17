# Changelog

All notable changes to `laraditz/razorpay` will be documented in this file

## Unreleased

## 1.1.2 - 2026-08-17

### Changed

- Docs — "Official Documentation" links across `docs/*.md` and `src/Razorpay.php` now point to `curlec.com/docs/...` instead of `razorpay.com/docs/...`, matching this package's Malaysia (Curlec) usage. The actual API host (`api.razorpay.com/v1`) is unchanged — Curlec merchants call the same gateway

## 1.1.1 - 2026-08-17

### Fixed

- `RefundService::create()` posted to `/payments/{payment_id}/refunds` — the "list refunds for a payment" path — instead of the correct create endpoint, `/payments/{payment_id}/refund` (singular). Every refund creation call was hitting the wrong endpoint

## 1.1.0 - 2026-08-17

### Added

- `SettlementService::fetchRecon()` — wraps Curlec's Settlement Recon endpoint (`GET /settlements/recon/combined`). Unlike `fetch()`/`all()`, which return settlement-batch summaries, this returns transaction-level detail: every payment, refund, transfer, and adjustment settled in a given year/month(/day), each tagged with its settlement batch and UTR. Single page per call — `count`/`skip` are forwarded as-is, with no auto-pagination
- `razorpay_settlement_transactions` table / `RazorpaySettlementTransaction` model, `RazorpaySettlementTransactionType` enum (`Payment`, `Refund`, `Transfer`, `Adjustment`) — one row per recon transaction line item, uniquely keyed on `entity_id` + `type`, populated via `RazorpaySettlementTransaction::syncFromResponse()`
- `RazorpaySettlement::transactions(): HasMany` / `RazorpaySettlementTransaction::settlement(): BelongsTo` — relates each recon line item back to its settlement batch via a nullable `settlement_id` reference

## 1.0.3 - 2026-08-03

### Fixed

- `razorpay_payment_links.order_id` stayed permanently `null` when it wasn't present in the Payment Link creation response (the common case — no payment attempted yet). `SyncPaymentLinkFromWebhook` now captures it from the `payment_link.paid` payload
- `razorpay_orders` never got a local row for Orders that only exist because they underlie a Payment Link — `SyncOrderFromWebhook` only ever updated an existing row (never created one), and `SyncPaymentLinkFromWebhook` didn't touch `razorpay_orders` at all, even though both `order.paid` and `payment_link.paid` carry the full embedded order entity. Fixed via a new `RazorpayOrder::syncFromResponse()` (`updateOrCreate()`-based, mirroring `RazorpayPayment`/`RazorpaySettlement`), now used by both webhooks and every `OrderService` method (`create()`/`fetch()`/`all()`/`update()`)

### Added

- `php artisan razorpay:backfill-payment-link-order-ids` — re-fetches every `razorpay_payment_links` row with a `null` `order_id` from Razorpay and backfills it if the response now has one; skips rows the API still doesn't return one for, and continues past individual failures rather than aborting the batch
- `RazorpayPaymentLink::order(): BelongsTo` — mirrors the existing `payment()` relationship; now resolves to something real since a matching `razorpay_orders` row will actually exist

## 1.0.2 - 2026-08-03

### Added

- Polymorphic `subject` relationship on `RazorpayOrder` and `RazorpayPaymentLink` — link either to any model in your app via a new `subject_id`/`subject_type` column pair, attached optionally at creation time through a new fluent `for()` method:
  ```php
  Razorpay::order()->for($myOrder)->create(['amount' => 50000]);
  Razorpay::paymentLink()->for($invoice)->create(['amount' => 50000]);
  ```
  `for()` is fully optional and backward compatible — every existing `create()` call is unaffected, `subject_id`/`subject_type` simply stay `null` if it's never called. Uses `getMorphClass()`, so a consuming app's `Relation::morphMap()` is respected if configured. Additive migrations only (`razorpay_orders`/`razorpay_payment_links` already shipped in v1.0.0/v1.0.1)

### Changed

- README — clarified the package description to reference Razorpay Curlec (curlec.com), Razorpay's Malaysia-specific brand

## 1.0.1 - 2026-08-02

### Fixed

- CI (`main.yml`) — disabled Composer's install-time security-advisory blocking (`config.policy.advisories.block: false`) via `composer.json`. Composer 2.9+ defaults to blocking resolution of any package version with a known security advisory; every installable `laravel/framework` 11.x release was affected by at least one of several open advisories, so the `laravel: 11.*` test matrix entry could no longer resolve any version at all. `--no-audit`/`COMPOSER_NO_AUDIT` do not affect this policy — only `composer.json`'s `config.policy.advisories.block` key does. No change to package behavior; CI/tooling only

## 1.0.0 - 2026-08-02

Initial release. A Laravel wrapper package for the Razorpay API, built directly on Laravel's HTTP client (no `razorpay/razorpay-php` SDK dependency) — Payment Links, Orders, Payments, Refunds, and Settlements, each with local database persistence and webhook-driven, event-based sync.

### Added

**Payment Links**
- `Razorpay::paymentLink()` — `create()`, `fetch()`, `update()`, `cancel()`, `all()`, `notifyBy()`
- `razorpay_payment_links` table / `RazorpayPaymentLink` model, `PaymentLinkStatus` enum

**Orders**
- `Razorpay::order()` — `create()`, `fetch()`, `all()`, `update()`, `fetchPayments()`
- `Razorpay::order()->verifyPaymentSignature()` for verifying Checkout.js callback signatures
- `razorpay_orders` table / `RazorpayOrder` model, `OrderStatus` enum

**Payments**
- `Razorpay::payment()` — `fetch()`, `capture()`, `update()`, `all()`
- `razorpay_payments` table / `RazorpayPayment` model, `PaymentStatus` enum
- Synced locally from both the API response of every call and the `payment.authorized`/`payment.captured`/`payment.failed` webhooks via a shared `RazorpayPayment::syncFromResponse()` method
- `OrderService::fetchPayments()` bulk-syncs every payment it returns

**Refunds**
- `Razorpay::refund()` — `create()`, `fetch()`, `all()`, `forPayment()`, `update()`
- `razorpay_refunds` table / `RazorpayRefund` model, `RefundStatus` enum

**Settlements**
- `Razorpay::settlement()` — `fetch()`, `all()`
- `razorpay_settlements` table / `RazorpaySettlement` model, `SettlementStatus` enum
- Synced locally from both the API response of every call and the `settlement.processed` webhook via a shared `RazorpaySettlement::syncFromResponse()` method

**Relationships**
- `payment(): BelongsTo` on `RazorpayOrder`, `RazorpayPaymentLink`, and `RazorpayRefund`, resolving the matching `RazorpayPayment` via a `payment_id` column, populated only when the record actually transitions to paid (never for a failed/retried attempt)

**Webhooks**
- Automatic webhook route (`POST /razorpay/webhook`, configurable via `RAZORPAY_WEBHOOK_PATH`), HMAC-SHA256 signature verification
- Generic `RazorpayWebhookReceived` event fires for every verified delivery, regardless of type
- Typed events, each keeping the matching local record in sync: `PaymentLinkPaid`, `PaymentAuthorized`, `PaymentCaptured`, `PaymentFailed`, `OrderPaid`, `RefundCreated`, `RefundProcessed`, `RefundFailed`, `SettlementProcessed`
- Idempotent sync — redelivered webhooks never produce duplicate or incorrect local state

**Logging**
- API request/response logging (`razorpay_api_logs` table / `RazorpayApiLog` model) — every outbound call recorded, with sensitive fields protected before storage, configurable toggle (`RAZORPAY_LOG_API_CALLS`) and retention (`RAZORPAY_API_LOG_RETENTION_DAYS`)
- Webhook audit log (`razorpay_webhook_logs` table / `RazorpayWebhookLog` model) — every signature-verified inbound webhook recorded, configurable toggle (`RAZORPAY_LOG_WEBHOOK_CALLS`) and retention (`RAZORPAY_WEBHOOK_LOG_RETENTION_DAYS`); signature failures are logged separately via the standard Laravel log channel instead
- Both logs support Laravel's built-in `model:prune` scheduling

**Core**
- `Razorpay` facade and manager class
- Package-owned exception hierarchy (`AuthenticationException`, `ValidationException`, `ApiException`) — raw HTTP client exceptions are never leaked
- Type-safe, string-backed PHP 8.2+ enums throughout
- Support for Laravel 10.x, 11.x, 12.x, and 13.x
