# Spec: Razorpay Payment Links Wrapper

## Overview
Build `laraditz/razorpay`, a Laravel package wrapping Razorpay's Payment Link REST API directly (no dependency on `razorpay/razorpay-php`), following the `Razorpay::paymentLink()->create()` facade pattern established by `laraditz/xendit`. This round ships the full Payment Link CRUD surface, HMAC-verified webhook handling with typed events, and local persistence auto-synced from webhooks. Orders API / Checkout.js is explicitly deferred.

## Codebase Context
`laraditz/razorpay` is a brand-new, empty package directory — there is no existing code in this repo to extend. Everything below is **new**, but the architecture is a deliberate 1:1 mirror of the sibling package `laraditz/xendit` (confirmed via direct file reads), reusing its proven shape rather than inventing a new one:

| xendit file (reused as template) | Razorpay equivalent | Notes |
|---|---|---|
| `src/Xendit.php` | `src/Razorpay.php` | Manager class, one method per resource |
| `src/Facades/Xendit.php` | `src/Facades/Razorpay.php` | Plain `Facade`, `@method static` PHPDoc |
| `src/XenditServiceProvider.php` | `src/RazorpayServiceProvider.php` | Singleton bindings, config merge, route loading, observer/listener registration |
| `src/Client/XenditClient.php` + `Concerns/{HandlesAuthentication,HandlesErrors,MakesHttpRequests}` | `src/Client/RazorpayClient.php` + same trait names | Auth header logic differs: Razorpay uses two credentials (`key_id:key_secret`), not one `api_key` |
| `src/Services/PaymentLinkService.php` | `src/Services/PaymentLinkService.php` | Same shape — thin class, one method per endpoint, returns array |
| `src/Exceptions/{XenditException,ApiException,AuthenticationException,ValidationException}.php` | `src/Exceptions/{RazorpayException,ApiException,AuthenticationException,ValidationException}.php` | Same hierarchy, same status-code matching |
| `src/Http/Controllers/WebhookController.php`, `Http/Middleware/VerifyXenditWebhook.php`, `Support/SignatureValidator.php`, `Support/WebhookHandler.php` | Same file names/roles | **Signature algorithm differs**: Xendit does `hash_equals($secret, $token)`; Razorpay requires `hash_equals($expected, hash_hmac('sha256', $rawBody, $secret))` over the **raw, unparsed** request body |
| `src/Models/XenditPayment.php`, `Observers/XenditPaymentObserver.php`, `Enums/PaymentStatus.php` | `src/Models/PaymentLink.php`, `Observers/PaymentLinkObserver.php`, `Enums/PaymentLinkStatus.php` | Deliberate deviation: enum is backed by Razorpay's own status strings (`created`, `partially_paid`, `paid`, `expired`, `cancelled`) rather than arbitrary ints — avoids a translation table between Razorpay's wire format and local storage |
| `database/migrations/..._create_xendit_payments_table.php` | `..._create_razorpay_payment_links_table.php` | New fields matching Razorpay's Payment Link entity, not Xendit's |
| `config/config.php` | `config/config.php` | `key_id` + `key_secret` instead of one `api_key`; no `api_versions` concept (Razorpay doesn't use per-service API version headers) |
| `tests/TestCase.php` | `tests/TestCase.php` | Same Orchestra Testbench + `RefreshDatabase` setup |
| `composer.json` | `composer.json` | Same shape: `laraditz/razorpay`, PSR-4 `Laraditz\Razorpay\`, PHP `^8.2`, `illuminate/support ^10\|^11\|^12`, dev deps `orchestra/testbench` + `phpunit/phpunit` |

Razorpay API details below (endpoints, fields, webhook envelope, signature scheme) were verified directly against Razorpay's official documentation (`razorpay.com/docs/api/payments/payment-links/`, `razorpay.com/docs/webhooks/`), not assumed from the Xendit pattern.

## Skills & Agents Available
- `test-driven-development` — applies throughout `iris-ops`: every task (client methods, exceptions, webhook verification, model sync) gets a failing test first
- No frontend/UI skills apply — this is a backend-only Laravel package with no views

## Functional Requirements

**Package bootstrap**
- FR-01: Package installs via `composer require laraditz/razorpay`; Laravel package auto-discovery registers `RazorpayServiceProvider` and the `Razorpay` facade alias (no manual provider registration required)
- FR-02: `php artisan vendor:publish --tag=razorpay-config` publishes `config/razorpay.php`; `--tag=razorpay-migrations` publishes the `razorpay_payment_links` migration

**Payment Link operations** (`Razorpay::paymentLink()` returns `PaymentLinkService`)
- FR-03: `create(array $data): array` — `POST /payment_links`. Persists a local `PaymentLink` record immediately from the response (see FR-18) and returns the decoded response body as a plain array
- FR-04: `fetch(string $id): array` — `GET /payment_links/{id}`, returns plain array, does not touch the local record
- FR-05: `update(string $id, array $data): array` — `PATCH /payment_links/{id}`, returns plain array
- FR-06: `cancel(string $id): array` — `POST /payment_links/{id}/cancel`. On success, also updates the matching local record's `status` to `cancelled` directly from the response — does not wait for the webhook
- FR-07: `all(array $query = []): array` — `GET /payment_links`, forwards supported query params (`payment_id`, `reference_id`, `upi_link`, `count`, `skip`) untouched, returns plain array (Razorpay's `{entity, count, items}` list envelope)
- FR-08: `notifyBy(string $id, string $medium): array` — `POST /payment_links/{id}/notify_by/{medium}` where `$medium` is `'sms'` or `'email'` (passed through as-is; no client-side validation of the value)

**Authentication & transport**
- FR-09: Every outbound call authenticates via HTTP Basic Auth: `Authorization: Basic base64(key_id:key_secret)`
- FR-10: Missing `key_id` or `key_secret` config throws `AuthenticationException` before any HTTP request is made (fail fast, mirrors `xendit`'s `getApiKey()` guard)
- FR-11: Requests are made via `Illuminate\Support\Facades\Http`, base URL `https://api.razorpay.com/v1` (configurable), JSON content type, configurable timeout

**Error handling**
- FR-12: Non-2xx responses are caught and rethrown as package exceptions, never as raw HTTP client exceptions:
  - HTTP 401 → `AuthenticationException`
  - HTTP 400 (validation errors) → `ValidationException`, carrying Razorpay's `error.field`/`error.description` detail
  - Other 4xx / 5xx → `ApiException`, carrying the full decoded response body
  - All extend a common base `RazorpayException`

**Webhooks**
- FR-13: A route is auto-registered by the ServiceProvider: `POST {config('razorpay.webhook_path', '/razorpay/webhook')}` → `WebhookController@handle`. Registered as a bare `Route::post(...)`, **not** wrapped in a `web` middleware group — this is what keeps Laravel's CSRF verification (which only applies within the `web` group) from ever seeing this route, satisfying the CSRF-exclusion requirement without any extra config
- FR-14: `VerifyRazorpayWebhook` middleware verifies the `X-Razorpay-Signature` header before the controller runs: `hash_equals(hash_hmac('sha256', $rawBody, config('razorpay.webhook_secret')), $signatureHeader)`, using the **raw, unparsed** request body (`$request->getContent()`), per Razorpay's documented requirement. Missing header, missing config, or mismatch → reject with `401`, controller body never executes, no event ever dispatches
- FR-15: On a verified webhook, a generic `RazorpayWebhookReceived` event always dispatches, carrying the event type string (`$payload['event']`) and the full raw payload array
- FR-16: Typed events additionally dispatch for this round's priority event types, each carrying the raw payload plus a best-effort match to a local `PaymentLink` model:
  - `payment_link.paid` → `PaymentLinkPaid`, matched via `payload.payment_link.entity.id` against `razorpay_id` (same key as FR-17)
  - `payment.captured` → `PaymentCaptured`, matched via `payload.payment.entity.order_id` against the local `order_id` column (see Data Model)
  - `payment.failed` → `PaymentFailed`, matched via `payload.payment.entity.order_id` against the local `order_id` column
  - If no local record matches (e.g. `order_id` absent from the payload, or the payment wasn't made through a link created by this package), the event still dispatches with a `null` model — listeners must handle that case
- FR-17: A package-owned listener (`SyncPaymentLinkFromWebhook`, registered on `RazorpayWebhookReceived` in the ServiceProvider's `boot()`) automatically updates the matching local `PaymentLink` record's `status` (and `paid_at`/`cancelled_at`/`expired_at` as applicable) when `payment_link.paid`, `payment_link.cancelled`, or `payment_link.expired` webhooks arrive, matched by `payload.payment_link.entity.id` against the local `razorpay_id` column

**Local persistence**
- FR-18: `create()` inserts a `PaymentLink` record synchronously, storing the key response fields plus the full response body in a `raw_response` JSON column
- FR-19: `PaymentLinkObserver::creating()` defaults `status` to `PaymentLinkStatus::Created` and `currency` to `config('razorpay.default_currency')` when not explicitly set — mirrors `XenditPaymentObserver`

## Non-Functional Requirements
- **Security**: signature comparison uses `hash_equals` (timing-safe); `key_secret` and `webhook_secret` are never logged or included in exception messages
- **Reliability**: the webhook sync listener is idempotent — re-delivering the same event (Razorpay retries on non-2xx) must not throw or duplicate side effects; a webhook referencing a `razorpay_id` with no matching local record no-ops silently rather than erroring
- **Testability**: every outbound HTTP call goes through `Illuminate\Support\Facades\Http`, so `Http::fake()` fully intercepts calls in tests — no real network access required to test any code path, including webhook signature generation (tests can compute a valid HMAC themselves using a known test secret)
- **Compatibility**: PHP `^8.2`, Laravel `illuminate/support ^10.0|^11.0|^12.0`

## Data Model

### `razorpay_payment_links` table (new)
| Column | Type | Notes |
|---|---|---|
| `id` | bigint, PK | |
| `razorpay_id` | string, unique, indexed | Razorpay's `id`, e.g. `plink_ExjpAUN3gVHrPJ` |
| `order_id` | string, nullable, indexed | Razorpay's underlying Order `id` for this link, from the create response. Used to match `payment.captured`/`payment.failed` webhooks back to this record via `payload.payment.entity.order_id` (see FR-16), since those payloads carry no direct payment-link reference |
| `status` | string, indexed | Backed by `PaymentLinkStatus` enum: `created`, `partially_paid`, `paid`, `expired`, `cancelled` |
| `amount` | unsigned integer | Smallest currency subunit, per Razorpay (e.g. paise for INR) — **not** decimal, unlike `xendit`'s `amount` column |
| `amount_paid` | unsigned integer, nullable | From Razorpay's response; updated on sync |
| `currency` | string(3) | ISO currency code |
| `reference_id` | string, nullable, indexed | Caller-supplied reference, unique per Razorpay's own constraint |
| `description` | text, nullable | |
| `customer_name` | string, nullable | From `customer.name` |
| `customer_email` | string, nullable | From `customer.email` |
| `customer_contact` | string, nullable | From `customer.contact` (Razorpay's field name — not `phone`) |
| `notify_sms` | boolean | From `notify.sms` |
| `notify_email` | boolean | From `notify.email` |
| `reminder_enable` | boolean | |
| `accept_partial` | boolean | |
| `first_min_partial_amount` | unsigned integer, nullable | |
| `notes` | json, nullable | Razorpay's free-form key-value metadata |
| `callback_url` | text, nullable | |
| `callback_method` | string, nullable | |
| `short_url` | text, nullable | Razorpay's hosted payment page URL |
| `raw_response` | json, nullable | Full decoded API response, for anything not promoted to a column |
| `expire_by` | timestamp, nullable | Cast from Razorpay's Unix timestamp |
| `paid_at` | timestamp, nullable | Set by the webhook sync listener |
| `cancelled_at` | timestamp, nullable | Set by `cancel()` directly, or by the webhook sync listener |
| `expired_at` | timestamp, nullable | Set by the webhook sync listener |
| `created_at` / `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | `SoftDeletes`, matching `xendit`'s convention |

Indexes: unique on `razorpay_id`; index on `reference_id`; index on `status`; composite index on `(status, created_at)` matching `xendit`'s query-pattern convention.

### `PaymentLinkStatus` enum (new)
String-backed: `Created`, `PartiallyPaid`, `Paid`, `Expired`, `Cancelled` — values exactly match Razorpay's own status strings (`created`, `partially_paid`, `paid`, `expired`, `cancelled`), so no translation table is needed between the wire format and local storage. Helper methods `isPaid()`, `isFinal()` mirror `PaymentStatus` in `xendit`.

## API Contracts

### External: Razorpay Payment Link API (verified against official docs — all new usage)
Base URL: `https://api.razorpay.com/v1`. Auth: HTTP Basic, `key_id` as username, `key_secret` as password.

| Method | Path | Purpose |
|---|---|---|
| POST | `/payment_links` | Create |
| GET | `/payment_links/{id}` | Fetch one |
| PATCH | `/payment_links/{id}` | Update |
| POST | `/payment_links/{id}/cancel` | Cancel |
| GET | `/payment_links` | List (query: `payment_id`, `reference_id`, `upi_link`, `count`, `skip`) |
| POST | `/payment_links/{id}/notify_by/{medium}` | Resend notification (`medium`: `sms` \| `email`) |

**Create request body**: `amount` (int, required), `currency` (string, optional), `accept_partial` (bool), `first_min_partial_amount` (int), `description` (string, ≤2048 chars), `reference_id` (string, ≤40 chars, unique), `customer.{name,contact,email}`, `notify.{sms,email}` (bool), `reminder_enable` (bool), `notes` (object, ≤15 pairs), `callback_url` (string), `callback_method` (must be `get` if `callback_url` set), `expire_by` (Unix timestamp, ≤6 months out).

**Response fields** (create/fetch/update/cancel/list all share this entity shape): `id`, `order_id`, `amount`, `amount_paid`, `currency`, `status` (`created`\|`partially_paid`\|`paid`\|`expired`\|`cancelled`), `short_url`, `created_at`, `updated_at`, `expire_by`, `expired_at`, `cancelled_at`, `customer`, `notify`, `reminder_enable`, `reminders`, `notes`, `reference_id`, `callback_url`, `callback_method`, `description`, `first_min_partial_amount`, `accept_partial`, `payments`, `user_id`, `whatsapp_link`.

### External: Razorpay Webhooks (verified against official docs)
- Signature header: `X-Razorpay-Signature` — `hash_hmac('sha256', $rawBody, $webhookSecret)`, verified with `hash_equals` against the **raw, unparsed** body
- Idempotency header: `x-razorpay-event-id` — unique per event delivery (available for future de-duplication, not required to store this round since the sync listener is idempotent by design)
- Envelope: `{ entity: "event", account_id, event: "payment_link.paid", contains: [...], payload: {...}, created_at }`
- `payment_link.paid` / `payment_link.partially_paid` payload: `payload.payment_link.entity`, `payload.order.entity`, `payload.payment.entity`
- `payment_link.cancelled` / `payment_link.expired` payload: `payload.payment_link.entity` only

### New: this package's own webhook endpoint
| Method | Path (configurable) | Middleware | Auth |
|---|---|---|---|
| POST | `/razorpay/webhook` | `VerifyRazorpayWebhook` only (no `web` group → no CSRF) | HMAC signature verification is the sole auth boundary |

## Chosen Implementation Approach
**Option A** (full directory structure mirroring `xendit` exactly — `Client/Concerns`, `Services`, `Models`, `Observers`, `Listeners`, `Events`, `Exceptions`, `Http/Controllers`, `Http/Middleware`, `Support`) was selected over a trimmed single-file-collapsed structure. Rationale: 100% reuse of a proven, already-battle-tested structure across `laraditz/*`; the "scaffolding for one file" cost in `Observers`/`Listeners` is temporary and avoids a near-certain refactor once Orders/Checkout.js (explicitly deferred, likely v2) needs its own model/observer/listener set.

## Edge Cases & Error Handling
- Missing `key_id`/`key_secret` config → `AuthenticationException` thrown before any request is attempted
- Non-2xx API response → mapped to `AuthenticationException` (401) / `ValidationException` (400, carries field errors) / `ApiException` (other 4xx/5xx, carries full body) — never a raw `Illuminate\Http\Client` exception
- Webhook request with missing or mismatched `X-Razorpay-Signature` → `401`, payload never parsed further, no event dispatches, no DB write occurs
- Webhook event type not in `{payment_link.paid, payment_link.cancelled, payment_link.expired, payment.captured, payment.failed}` → generic `RazorpayWebhookReceived` still fires; no typed event; no model sync (out of scope for typed handling this round, per brief)
- Webhook references a `razorpay_id` with no matching local `PaymentLink` (e.g. link created outside this package) → sync listener no-ops silently, does not throw
- Duplicate webhook delivery (Razorpay retries on non-2xx) for an already-synced status → update is idempotent; setting the same status again is a safe no-op
- `cancel()` called on an already-cancelled or already-paid link → Razorpay's API itself returns a 4xx, surfaced as `ValidationException`/`ApiException`; package does not pre-check local state before calling the API
- `notifyBy()` called with a medium other than `sms`/`email` → Razorpay's API rejects with 400 → `ValidationException`; the package does not client-side validate `$medium`, matching `xendit`'s "let the API be the source of truth for valid inputs" convention
- `payment.captured`/`payment.failed` webhook with no `order_id` in the payload, or an `order_id` not matching any local record → `PaymentCaptured`/`PaymentFailed` event still dispatches with a `null` model; no exception, no DB write
- `expire_by` in the create/update request is accepted as a raw Unix timestamp (int), matching Razorpay's wire format; the local model casts its own `expire_by` column to a Carbon datetime for querying convenience

## Out of Scope
- Orders API + Checkout.js integration (client-side "pay on my site" flow, order creation, `verifyPaymentSignature`) — deferred to a future round, not scaffolded
- Subscriptions, Payment Pages, Refunds, or any Razorpay resource other than Payment Links
- Any dependency on `razorpay/razorpay-php` (explicitly reversed during brief)
- Typed webhook events/local-model sync for any event type beyond `payment_link.paid`/`.cancelled`/`.expired`, `payment.captured`, `payment.failed` — everything else only reaches the generic `RazorpayWebhookReceived` event
