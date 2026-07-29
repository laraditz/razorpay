# Brief: Razorpay Payment Links Wrapper

## Goal
Build `laraditz/razorpay`, a Laravel wrapper package for Razorpay's Payment Link API, so consuming apps can create and manage payment links and confirm payment via webhook using the same `Razorpay::paymentLink()->create()` facade pattern established across the `laraditz/*` package family.

## Context
The user maintains a family of Laravel API-wrapper packages (`laraditz/xendit`, `laraditz/shopee`, `laraditz/lazada`, `laraditz/tng-ewallet`) that all share one architecture: a facade → manager class → per-resource service classes, calling REST APIs directly via Laravel's `Http` facade (no vendor SDK dependency), with `HandlesAuthentication`/`HandlesErrors`/`MakesHttpRequests` traits on a client class.

Razorpay ships an official PHP SDK (`razorpay/razorpay-php`), and the initial plan was to depend on it and delegate to its `Api` client. Mid-brief, that decision was revisited and reversed: once the requirements settled on plain-array responses, package-specific exceptions, and a package-owned webhook sync layer, none of the SDK's typed entities or exception hierarchy would actually surface in the public API — the SDK would be a dependency paying for itself only via `Utility::verifyWebhookSignature()`, which is trivial and well-documented to reimplement directly. Going SDK-free also makes the package testable with `Http::fake()` instead of mocking the SDK's magic `__get` resource properties, matching the sibling packages' test setup exactly.

Priority for this round, per the user: "be able to make payment." The user's example call — `Razorpay::paymentLink()->create()` — set Payment Links as the priority resource over Orders/Checkout.js (Razorpay's client-side "pay on my site" flow).

## Scope

### In
- Facade `Razorpay` → manager class `Razorpay` → `PaymentLinkService`, mirroring `xendit`'s `XenditClient` + trait shape (`HandlesAuthentication`, `HandlesErrors`, `MakesHttpRequests`)
- Direct REST calls to Razorpay's Payment Link API via Laravel's `Http` facade, Basic Auth (`key_id:key_secret`) — no `razorpay/razorpay-php` dependency
- Payment Link operations, full set: create, fetch, update, cancel, list, resend notification (`notify_by`)
- Responses returned as plain arrays (not typed DTOs or SDK entities), consistent with sibling packages
- Package-specific exception hierarchy: base `RazorpayException`, plus subclasses distinguishing authentication, validation, and general API errors — no vendor/SDK exception classes exposed
- Webhook handling:
  - Auto-registered route (e.g. `POST /razorpay/webhook`), config-driven path, CSRF-excluded, no additional auth middleware (HMAC signature verification is the security boundary)
  - Hand-rolled signature verification (HMAC-SHA256 over the raw request body against the configured webhook secret, per Razorpay's documented scheme)
  - A generic `RazorpayWebhookReceived` event fires on every verified webhook with the raw payload + event type string
  - Typed events for this round's priority events: `PaymentLinkPaid`, `PaymentCaptured`, `PaymentFailed`
- Local persistence: migrations + an Eloquent `PaymentLink` model
  - `create()` inserts the local record at creation time
  - A package-owned webhook listener automatically syncs the local record's status (paid/cancelled/expired) as the corresponding webhook events arrive — no manual sync required in consuming apps
- Testing: PHPUnit + Orchestra Testbench, `Http::fake()` for API mocking — matching the `xendit` test setup
- Package conventions matching `xendit`: PSR-4 `Laraditz\Razorpay\`, PHP `^8.2`, `illuminate/support ^10|^11|^12`, Laravel package auto-discovery (ServiceProvider + facade alias)

### Out
- Orders API + Checkout.js integration (client-side "pay on my site" flow, order creation + payment signature verification) — explicitly deferred, not even scaffolded this round
- Subscriptions, Payment Pages, Refunds, or any Razorpay resource other than Payment Links
- Any dependency on `razorpay/razorpay-php` (reversed mid-brief in favor of a direct REST client)

## Constraints
- Must follow the established `laraditz/*` facade/manager/service architecture exactly (no vendor SDK dependency, `Http::fake()`-testable)
- Public response contract is plain arrays and package-owned exception types — never leak vendor/SDK types
- Webhook route must exclude CSRF (Razorpay's POST carries no CSRF token) since signature verification is the actual auth mechanism

## Open Questions Resolved
| Question | Answer |
|---|---|
| Priority Razorpay payment flow for this round? | Payment Links (matches the example `Razorpay::paymentLink()->create()`); Orders/Checkout deferred entirely |
| Which Payment Link operations are in scope? | Full set — create, fetch, update, cancel, list, resend notification |
| Is webhook handling in scope? | Yes — full signature verification + event parsing, including a route/controller |
| Depend on `razorpay/razorpay-php` SDK, or call the REST API directly? | Reversed mid-brief: no SDK dependency — direct REST client via `Http` facade, matching sibling packages |
| What should `create()` return — SDK entity, plain array, or DTO? | Plain array (`toArray()`-equivalent), consistent with sibling packages |
| How should the manager be structured for testability? | Resolved automatically by the SDK-free decision: `Http::fake()`, matching `xendit`'s test setup — no mocking layer needed |
| Catch and rewrap SDK/API exceptions, or let them bubble up raw? | Catch and rewrap into a package-specific exception hierarchy (`RazorpayException` + subclasses) |
| Auto-register the webhook route, or ship a controller only? | Auto-register (config-driven path), matching `xendit`'s pattern |
| CSRF handling on the webhook route? | Explicitly excluded/documented — Laravel's CSRF middleware would otherwise reject Razorpay's POST with a 419 |
| Generic webhook event, typed events, or both? | Both — a generic `RazorpayWebhookReceived` event always fires, plus typed events (`PaymentLinkPaid`, `PaymentCaptured`, `PaymentFailed`) for this round's priority cases |
| Local DB persistence of Payment Links, or stateless API client? | Persist locally — migrations + Eloquent `PaymentLink` model, mirroring `xendit` |
| How does the local record stay in sync with Razorpay's actual state? | Auto-sync: a package-owned webhook listener updates the local record's status automatically as paid/cancelled/expired events arrive |
