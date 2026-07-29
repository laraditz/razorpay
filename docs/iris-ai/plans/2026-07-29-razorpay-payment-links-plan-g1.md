# Group 1: Foundation

**Branch:** feature/foundation
**Status:** pending
**Parent plan:** 2026-07-29-razorpay-payment-links-plan.md

## Tasks

### Task 1 — composer.json
- **What:** `composer.json` (`laraditz/razorpay`, PSR-4 `Laraditz\Razorpay\`, PHP `^8.2`, `illuminate/support ^10|^11|^12`, dev deps `orchestra/testbench`+`phpunit/phpunit`)
- **Test first:** n/a (config file)
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 2 — config/config.php
- **What:** `config/config.php` (`key_id`, `key_secret`, `base_url` default `https://api.razorpay.com/v1`, `timeout`, `default_currency`, `webhook_secret`, `webhook_path` default `/razorpay/webhook`)
- **Test first:** n/a (config file)
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 3 — Minimal RazorpayServiceProvider
- **What:** Minimal `RazorpayServiceProvider` — `register()` merges config only
- **Test first:** n/a, covered by Task 4's smoke test
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 4 — tests/TestCase.php
- **What:** `tests/TestCase.php` (Orchestra Testbench + `RefreshDatabase`, registers `RazorpayServiceProvider`, sets test `key_id`/`key_secret`/`webhook_secret`, loads package migrations)
- **Test first:** `test description`: smoke test asserting `config('razorpay.key_id')` resolves inside a `TestCase`-based test
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 5 — RazorpayException base class
- **What:** `RazorpayException` base class
- **Test first:** `test description`: asserts it extends `\Exception` and is throwable/catchable
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 2 min

### Task 6 — AuthenticationException
- **What:** `AuthenticationException extends RazorpayException`
- **Test first:** `test description`: asserts inheritance + message passthrough
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 2 min

### Task 7 — ValidationException
- **What:** `ValidationException extends RazorpayException` with `getErrors(): array`
- **Test first:** `test description`: asserts `getErrors()` returns constructor-provided errors array
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 8 — ApiException
- **What:** `ApiException extends RazorpayException` with `getResponse(): array`
- **Test first:** `test description`: asserts `getResponse()` returns constructor-provided body array
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 9 — PaymentLinkStatus enum
- **What:** `PaymentLinkStatus` string-backed enum (`Created`,`PartiallyPaid`,`Paid`,`Expired`,`Cancelled` = Razorpay's own strings) + `isPaid()`/`isFinal()`
- **Test first:** `test description`: asserts each case's backing value and both helper methods
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 10 — Migration: razorpay_payment_links
- **What:** Migration `create_razorpay_payment_links_table` (every column from the spec's Data Model section, incl. `order_id`)
- **Test first:** `test description`: `Schema::getColumnListing('razorpay_payment_links')` contains every documented column
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 11 — PaymentLink model
- **What:** `PaymentLink` model (`fillable`, casts: `status`→enum, `amount`/`amount_paid`/`first_min_partial_amount`→int, `notes`/`raw_response`→array, boolean casts, `expire_by`/`paid_at`/`cancelled_at`/`expired_at`→datetime, `SoftDeletes`)
- **Test first:** `test description`: creates a record, asserts each cast type (incl. `expire_by` resolves to a `Carbon` instance)
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 12 — PaymentLinkObserver
- **What:** `PaymentLinkObserver::creating()` defaults `status`→`Created`, `currency`→`config('razorpay.default_currency')` when unset; registered in `ServiceProvider::boot()`
- **Test first:** `test description`: creating without `status`/`currency` picks up defaults; explicit values are not overridden
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 13 — Publish config + migrations
- **What:** `ServiceProvider::boot()` publishes `config/config.php`→`config_path('razorpay.php')` (tag `razorpay-config`) and the migration file (tag `razorpay-migrations`), only when `runningInConsole()` — mirrors `xendit`'s `publishMigrations()`
- **Test first:** `test description`: Testbench `artisan('vendor:publish', ['--tag' => 'razorpay-config'])` results in the config file existing at the published path
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min
