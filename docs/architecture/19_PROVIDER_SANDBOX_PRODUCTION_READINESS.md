# Engine 16 - Provider Sandbox & Production Readiness

Branch: `codex/engine-16-provider-sandbox-production-readiness`

## Goal

Engine 16 verifies production readiness for the backend architecture delivered by Engines 11-15. It does not add admin UI and does not add a new pricing, billing, queue, or notification system.

## Architecture

```text
Order / Admin action
    -> Manager
    -> Operation Queue
    -> Cron
    -> Processor
    -> Provider Adapter
    -> Sanitized response
    -> Service / Billing / Notification / Monitoring updates
```

## New Backend Class

`NtRcProductionReadinessVerifier`

Responsibilities:

- Check provider contract guard behavior.
- Check DomainNameAPI SSL/hosting blocking.
- Check SSL manager queue entrypoints.
- Check queue processor inheritance.
- Check Engine 11 pricing integration.
- Check Engine 13 billing integration.
- Check notification template keys.
- Check monitoring availability.
- Check runtime/shared-hosting guard availability.
- Check that table creation SQL remains in installer/schema migration code.

The verifier does not call real provider endpoints.

## Schema Rule

Engine 16 did not add a table.

Runtime table creation SQL was removed from:

- `NtRcHostingProductMappingManager`
- `NtRcBillingEventManager`

Both now delegate schema checks to `NtRcInstaller`.

## Provider Rules

| Service | Provider | Production Rule |
|---|---|---|
| Global domain | ResellerClub | Allowed |
| TR domain | DomainNameAPI | Allowed only for TR TLDs |
| Hosting | ResellerClub | DomainNameAPI blocked |
| SSL | ResellerClub | DomainNameAPI blocked |

Unverified provider endpoints must return controlled failure/TODO responses.

## SSL Status

Ready backend pieces:

- product mapping,
- queue actions,
- service lifecycle fields,
- ResellerClub SSL adapter,
- SSL monitoring,
- notification templates,
- billing/payment-required integration,
- pricing integration.

Controlled TODO:

- `renewSsl()` until current official ResellerClub parameter contract is verified.
- secure CSR/raw certificate handling for future customer-facing flows.

## Shared Hosting

Shared-hosting compatibility is preserved:

- hook path stays light,
- cron batch is capped,
- heavy work is queue-driven,
- mail is queue-driven,
- provider calls are centralized.

## Production Readiness Outcome

Backend is ready for controlled sandbox verification and production hardening. Full SSL commercial launch should wait for the remaining renew contract and secure CSR/certificate handling decisions.
