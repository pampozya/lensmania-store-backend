# Security Architecture

## Scope and trust model
- All trust-sensitive decisions are server-authoritative:
  - Price, product, discount, entitlement, fulfillment, and license state.
  - Webhook signature verification and capture reconciliation.
  - License grace one-time consumption/clear tracking (`license_grace`) and device slot management.
- Client may only precompute presentation values and offline short-circuit checks.
- Money values are always server computed and stored in integer cents in USD.
- All secrets live only in `.env`.

## Grace model (authoritative)
State scope is **per `(license_id, device_id)`**, while activation slot enforcement is **per license**.

### Core invariant
At any moment, enforce `active_device_count(license_id) <= 2`, counting only rows with `license_devices.status = 'active'`.
That means these states occupy a slot:
- `ACTIVE_ONLINE`
- `GRACE_LOCKED`
- `GRACE_EXPIRED`
These states do NOT occupy a slot:
- `DEACTIVATED`
- `NO_RECORD`

`LICENSE_REVOKED` blocks all transitions.

### States
- `NO_RECORD`
- `ACTIVE_ONLINE`
- `GRACE_LOCKED`
- `GRACE_EXPIRED`
- `DEACTIVATED`
- `LICENSE_REVOKED`

### Events
- `E1`: `/api/license/activate`
- `E2`: `/api/license/validate`
- `E3`: offline app launch attempt (no network)
- `E4`: online validate request contains grace claim (`grace_used=true`, claim payload)
- `E5`: `/api/license/deactivate`

### Corrected transition table

| From | Event | Guard | To | Result | Side effects |
|---|---|---|---|---|---|
| NO_RECORD | E1 | license active, valid request, per-license active device count < 2 | ACTIVE_ONLINE | Allow | create/refresh `license_devices` row; set `first_activated_at` if null; set `last_validated_at=now`; issue token `grace_used=false`, `grace_started_at=null`, `requires_online_after=last_validated_at` |
| NO_RECORD | E2 | — | NO_RECORD | Deny | must activate first; no token issued |
| NO_RECORD | E3 | — | NO_RECORD | Deny | block app launch |
| ACTIVE_ONLINE | E2 | online signature valid, anti-rollback pass, token valid, no tamper | ACTIVE_ONLINE | Allow | refresh `last_validated_at`; clear `license_grace` if present and stale; reissue token with `grace_used=false`, `grace_started_at=null` |
| ACTIVE_ONLINE | E3 | valid local signed token AND clock rollback checks pass AND `grace_used == false` AND no uncleared `license_grace` row for this `(license_id, device_id)` | GRACE_LOCKED | Allow | persist local token update `grace_used=true`, `grace_started_at=monotonic_now` for this device |
| GRACE_LOCKED | E2 | online signature valid, anti-rollback pass | ACTIVE_ONLINE | Allow | refresh `last_validated_at`; reissue token with grace reset; write `license_grace.used_at` as consumed claim, then set `license_grace.cleared_at=now` |
| GRACE_LOCKED | E2+E4 | grace claim present and token claim signature consistent | ACTIVE_ONLINE | Allow | same as above; clear claim by `license_grace.cleared_at=now` |
| GRACE_LOCKED | E3 | `no network` and `now_monotonic - grace_started_at <= 10800` and grace already tied to active license/device and not cleared | GRACE_LOCKED | Allow | keep local allow state until expiry |
| GRACE_LOCKED | E3 | `now_monotonic - grace_started_at > 10800` OR grace already consumed and not yet cleared (stale replay) | GRACE_EXPIRED | Deny | hard-stop offline app until successful online validate |
| GRACE_EXPIRED | E2 | online signature valid, anti-rollback pass | ACTIVE_ONLINE | Allow | clear expired/blocked grace record and reset local grace flags |
| GRACE_EXPIRED | E5 | valid deactivation request | DEACTIVATED | Allow | clear device token/grace state and remove active slot |
| GRACE_EXPIRED | E3 | any | GRACE_EXPIRED | Deny | hard-stop offline |
| ACTIVE_ONLINE | E5 | valid self-service deactivate request | DEACTIVATED | Allow | invalidate token/grace for this device, free slot |
| DEACTIVATED | E1 | valid re-activation request | ACTIVE_ONLINE | Allow | allocate slot under normal activation path |
| LICENSE_REVOKED | Any | license status revoked | LICENSE_REVOKED | Deny | lock all license usage |

## Client vs server enforcement
- **Offline 3-hour countdown + allow/deny**: enforced by client using a signed local entitlement store and monotonic clock.
  - local fields: `grace_started_at`, `grace_used`, `issued_at`, `requires_online_after`, signed payload.
  - client refusal when `clock < requires_online_after` (rollback) or grace expired without a fresh online clear.
- **Server as source of truth**: writes and validates one-time grace consumption (`license_grace.used_at/cleared_at`) and refuses to clear or allow re-arming if claim is inconsistent.
- Client can lengthen local timers, but cannot re-arm grace without successful online E2 that sets `license_grace.cleared_at=now`.

### State boundaries
- State is persisted per `(license_id, device_id)`.
- Enforcement (slot capacity) is evaluated per `license_id`.
- `GRACE_EXPIRED` is a terminal-ish lock state: it only exits on `E2` (online validate) to `ACTIVE_ONLINE` or `E5` (deactivate) to `DEACTIVATED`.
