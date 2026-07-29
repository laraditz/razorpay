# Group 2: HTTP Client & Auth

**Branch:** feature/http-client
**Status:** pending
**Parent plan:** 2026-07-29-razorpay-payment-links-plan.md

## Tasks

### Task 1 — HandlesAuthentication trait
- **What:** `HandlesAuthentication` trait — `getAuthHeaders()` builds `Authorization: Basic base64(key_id:key_secret)`
- **Test first:** `test description`: missing `key_id` or `key_secret` throws `AuthenticationException`; valid config produces the correct header value
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 2 — MakesHttpRequests trait
- **What:** `MakesHttpRequests` trait — `buildClient()` sets base URL, timeout, `Content-Type`/`Accept: application/json`, merges auth headers
- **Test first:** `test description`: `Http::fake()`, assert the outgoing request has the expected base URL, headers, and timeout
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 3 — HandlesErrors::handleResponse()
- **What:** `HandlesErrors::handleResponse()` — returns `$response->json() ?? []` on success
- **Test first:** `test description`: 2xx fake response returns the decoded array
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min

### Task 4 — HandlesErrors::throwException()
- **What:** `HandlesErrors::throwException()` — maps 401→`AuthenticationException`, 400→`ValidationException` (with errors), other 4xx/5xx→`ApiException` (with body)
- **Test first:** `test description`: one assertion per status-code branch using faked responses
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 5 — ClientInterface + get()/post()
- **What:** `ClientInterface` contract + `RazorpayClient::get()`/`post()`
- **Test first:** `test description`: `Http::fake()`, assert `get`/`post` call the right endpoint/query/body and return the decoded array via `handleResponse()`
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 6 — put()/patch()/delete()
- **What:** `RazorpayClient::put()`/`patch()`/`delete()`
- **Test first:** `test description`: same pattern as Task 5 for the remaining three verbs
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 5 min

### Task 7 — Register RazorpayClient singleton
- **What:** Register `RazorpayClient` as a singleton in `ServiceProvider::register()`
- **Test first:** `test description`: `app(RazorpayClient::class)` resolves and returns the same instance on repeat calls
- **Agent:** iris (default)
- **Subagent:** no
- **Est:** 3 min
