# Group 4: Webhooks

**Branch:** feature/webhooks
**Status:** pending
**Parent plan:** 2026-07-29-razorpay-payment-links-plan.md

## Tasks

### Task 1 — SignatureValidator
- **What:** `SignatureValidator` — `verify(string $rawBody, string $signature): bool` via `hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature)`; missing `webhook_secret` throws `WebhookException`
- **Test first:** `test description`: valid HMAC passes, tampered body/signature fails, missing secret throws
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 2 — VerifyRazorpayWebhook middleware
- **What:** `VerifyRazorpayWebhook` middleware — rejects with `401` on missing/invalid `X-Razorpay-Signature` before the controller runs; passes through on valid signature
- **Test first:** `test description`: invalid signature → `401`, controller never reached; valid signature → request passes through
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 3 — Webhook route registration
- **What:** Webhook route — bare `Route::post(config('razorpay.webhook_path'), ...)` (no `web` group, so no CSRF), loaded via `loadRoutesFrom()` in `ServiceProvider::boot()`
- **Test first:** `test description`: feature test posting a validly-signed payload to the configured path reaches the controller and returns `200`
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 4 — Webhook event classes
- **What:** Event classes: `RazorpayWebhookReceived(eventType, payload)`, `PaymentLinkPaid(model, payload)`, `PaymentCaptured(model, payload)`, `PaymentFailed(model, payload)` (`Dispatchable`, `SerializesModels`)
- **Test first:** `test description`: each event stores its constructor args on public properties
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 5 — WebhookController::handle()
- **What:** `WebhookController::handle()` — decodes payload, delegates to `WebhookHandler`, returns `200` JSON on success / `500` JSON on handler exception
- **Test first:** `test description`: valid webhook request returns `200` with expected JSON shape
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 6 — Generic webhook event dispatch
- **What:** `WebhookHandler` dispatches generic `RazorpayWebhookReceived` for every verified webhook
- **Test first:** `test description`: `Event::fake()`, assert `RazorpayWebhookReceived` dispatches with the correct event-type string and payload for both a known and an *unknown* event type (unknown type still dispatches the generic event, nothing else)
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 7 — PaymentLinkPaid dispatch + matching
- **What:** `WebhookHandler` resolves + dispatches `PaymentLinkPaid` for `payment_link.paid`, matching `payload.payment_link.entity.id` against local `razorpay_id`
- **Test first:** `test description`: matching record → event carries the model; no match → event carries `null` model, no exception
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 8 — PaymentCaptured/PaymentFailed dispatch + matching
- **What:** `WebhookHandler` resolves + dispatches `PaymentCaptured`/`PaymentFailed` for `payment.captured`/`payment.failed`, matching `payload.payment.entity.order_id` against local `order_id`
- **Test first:** `test description`: matching record → event carries the model; no `order_id` in payload or no match → event carries `null` model
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 9 — SyncPaymentLinkFromWebhook listener
- **What:** `SyncPaymentLinkFromWebhook` listener — on `payment_link.paid`/`.cancelled`/`.expired`, updates the matching `PaymentLink`'s `status` + `paid_at`/`cancelled_at`/`expired_at`
- **Test first:** `test description`: each of the three event types updates the correct field(s) on the matching record
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 10 — Listener idempotency + no-match handling
- **What:** Idempotency + no-match handling for `SyncPaymentLinkFromWebhook`
- **Test first:** `test description`: re-delivering the same event twice doesn't throw or produce a different result than once; an event referencing a `razorpay_id` with no local match no-ops without throwing
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 11 — Register sync listener
- **What:** Register `Event::listen(RazorpayWebhookReceived::class, SyncPaymentLinkFromWebhook::class)` in `ServiceProvider::boot()`
- **Test first:** `test description`: end-to-end — POST a validly-signed `payment_link.paid` webhook to the route, assert the local `PaymentLink` row's status is `paid` afterward
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min
