# Group 3: Payment Link Service & Facade

**Branch:** feature/payment-link-service
**Status:** pending
**Parent plan:** 2026-07-29-razorpay-payment-links-plan.md

## Tasks

### Task 1 — PaymentLinkService::create()
- **What:** `PaymentLinkService::create(array $data): array` — `POST /payment_links`
- **Test first:** `test description`: `Http::fake()`, assert correct endpoint/body sent and decoded array returned
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 2 — create() persists local record
- **What:** `create()` also persists a local `PaymentLink` record from the response (`razorpay_id`, `order_id`, `status`, `amount`, etc. mapped; full body into `raw_response`)
- **Test first:** `test description`: after `create()`, a matching `PaymentLink` row exists with correctly mapped fields
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 3 — PaymentLinkService::fetch()
- **What:** `PaymentLinkService::fetch(string $id): array` — `GET /payment_links/{id}`, does not touch the local record
- **Test first:** `test description`: returns decoded array; asserts no `PaymentLink` row is created/modified
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 4 — PaymentLinkService::update()
- **What:** `PaymentLinkService::update(string $id, array $data): array` — `PATCH /payment_links/{id}`
- **Test first:** `test description`: correct endpoint/method/body, decoded array returned
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 5 — PaymentLinkService::cancel()
- **What:** `PaymentLinkService::cancel(string $id): array` — `POST /payment_links/{id}/cancel`, then updates the matching local record's `status`→`Cancelled`/`cancelled_at` directly from the response
- **Test first:** `test description`: local record status updates immediately on success; a faked 4xx response propagates as `ValidationException`/`ApiException` without touching the local record
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 6 — PaymentLinkService::all()
- **What:** `PaymentLinkService::all(array $query = []): array` — `GET /payment_links` forwarding `payment_id`/`reference_id`/`upi_link`/`count`/`skip`
- **Test first:** `test description`: query params are forwarded untouched; list envelope array returned
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 7 — PaymentLinkService::notifyBy()
- **What:** `PaymentLinkService::notifyBy(string $id, string $medium): array` — `POST /payment_links/{id}/notify_by/{medium}`
- **Test first:** `test description`: correct endpoint built with `$medium`; an invalid medium is passed through untouched (no client-side validation) and a faked 400 propagates as `ValidationException`
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 8 — Razorpay manager class
- **What:** `Razorpay` manager class — `paymentLink(): PaymentLinkService`
- **Test first:** `test description`: `app('razorpay')->paymentLink()` returns a `PaymentLinkService` instance wired to the shared client
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 9 — Razorpay facade
- **What:** `Facades/Razorpay.php` facade (`@method static` PHPDoc, accessor `'razorpay'`) + register `'razorpay'` singleton in `ServiceProvider::register()`
- **Test first:** `test description`: `Razorpay::paymentLink()->create($data)` resolves end-to-end through the facade with `Http::fake()`
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 10 — Package auto-discovery
- **What:** `composer.json` `extra.laravel` auto-discovery entries (`providers`, `aliases.Razorpay`)
- **Test first:** n/a (config file)
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 2 min
