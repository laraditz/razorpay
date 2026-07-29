# Plan: Razorpay Payment Links Wrapper

**Spec:** [docs/iris-ai/specs/2026-07-29-razorpay-payment-links-spec.md](../specs/2026-07-29-razorpay-payment-links-spec.md)
**Date:** 2026-07-29

## Groups

| # | Group | Branch | Status | File |
|---|---|---|---|---|
| 1 | Foundation | `feature/foundation` | pending | 2026-07-29-razorpay-payment-links-plan-g1.md |
| 2 | HTTP Client & Auth | `feature/http-client` | pending | 2026-07-29-razorpay-payment-links-plan-g2.md |
| 3 | Payment Link Service & Facade | `feature/payment-link-service` | pending | 2026-07-29-razorpay-payment-links-plan-g3.md |
| 4 | Webhooks | `feature/webhooks` | pending | 2026-07-29-razorpay-payment-links-plan-g4.md |

## Sequencing Notes
Group 1 must complete first (config, exceptions, model, migrations — everything else depends on it). Group 2 depends on Group 1 only. Group 3 depends on Groups 1–2. Group 4 depends on Group 1 only, not Group 3 — it could run in parallel with Group 3, but is sequenced last here since it's the most novel logic (HMAC verification, webhook envelope parsing) and benefits from the rest of the package being stable first.
