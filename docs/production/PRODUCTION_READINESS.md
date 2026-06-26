# Production Readiness

Engine 16 verifies the backend readiness of the NetwoTurk PrestaShop module after Engines 11-15. It does not add admin UI and does not execute real provider calls.

## Production Gate

Production is allowed only when all of these are true:

- Module install/upgrade is run through the installer schema path.
- No runtime manager owns table creation SQL outside `NtRcInstaller`.
- Heavy operations go through operation queue and cron processors.
- Notifications go through notification queue and cron mail sender.
- Pricing uses Engine 11 pricing/mapping classes.
- Billing uses Engine 13 billing event and order orchestration classes.
- ResellerClub is the only provider for hosting and SSL.
- DomainNameAPI is limited to TR domain operations.
- Sensitive provider/customer/certificate fields are sanitized before logs, queue errors, and admin output.

## Backend Verifier

`NtRcProductionReadinessVerifier::summary()` performs a local, no-network readiness check.

It validates:

- provider contract guard behavior,
- SSL queue entrypoints,
- SSL -> hosting -> base queue processor inheritance,
- billing-aware processor inheritance,
- pricing product type support,
- billing event manager availability,
- SSL monitoring availability,
- notification template keys,
- runtime guard methods,
- table creation SQL placement.

This verifier is safe for admin diagnostics because it does not call ResellerClub or DomainNameAPI.

## Shared Hosting Rules

- Hooks must stay lightweight.
- Cron batch size is capped by `NtRcRuntimeGuard::cronBatchLimit()`.
- Heavy processors call runtime guards before batch work.
- Provider HTTP is centralized in `NtRcApiClient`.
- Mail send is centralized in `NtRcNotificationQueueManager`.

## Security Rules

The following must never be logged or echoed raw:

- api-key
- password
- credential
- csr
- private key
- certificate raw
- token
- auth-code
- raw request

Adapters and billing events must sanitize both success and failure payloads.

## Current Readiness

Backend architecture is production-ready for queue-based operation, installer-managed schema, notification queue, monitoring metrics, pricing integration, and billing integration.

Known blockers before full commercial SSL launch:

- ResellerClub SSL renew endpoint needs current official parameter verification.
- Secure customer-facing CSR/certificate download storage policy is still required.
- Real PrestaShop 1.7/8/9 smoke tests must be run in a module environment.
