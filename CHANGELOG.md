# Changelog

All notable changes to `laraditz/razorpay` will be documented in this file

## Unreleased

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
