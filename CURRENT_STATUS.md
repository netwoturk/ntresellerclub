# Current Status

Date: 2026-06-26

## Last Work

Engine 16 - Provider Sandbox & Production Readiness Engine

## Last Branch

`codex/engine-16-provider-sandbox-production-readiness`

## Completed

- Added backend-only production readiness verification through `NtRcProductionReadinessVerifier`.
- Verified provider contract rules without real provider API calls:
  - ResellerClub remains allowed for global domain, hosting, and SSL.
  - DomainNameAPI remains limited to TR domain operations.
  - DomainNameAPI SSL and hosting actions remain blocked by contract guard.
- Verified queue architecture for SSL, hosting, billing, notification, and cron-safe processing.
- Verified Engine 11 pricing integration is reused for SSL and hosting mappings.
- Verified Engine 13 billing event manager remains the billing integration point.
- Removed manager-owned runtime table creation SQL from hosting product mapping and billing event manager; both now delegate schema work to `NtRcInstaller`.
- Added production, sandbox, regression, and Engine 16 architecture documentation.
- Updated API contract, database, roadmap, changelog, and current-status documentation.

## Production Readiness

- Heavy provider work remains queue -> cron -> processor.
- Hooks only enqueue/delegate and do not run provider API calls directly.
- Mail sending remains notification queue -> cron -> `Mail::Send`.
- Runtime caps remain controlled by `NtRcRuntimeGuard` and shared-hosting batch limits.
- Sensitive fields remain blocked from logs and payload echo:
  - api-key
  - password
  - credential
  - csr
  - private key
  - certificate raw
  - token
  - auth-code

## Database Changes

No new table was added in Engine 16.

Schema responsibility changed:

- `NtRcHostingProductMappingManager::ensureSchema()` now calls `NtRcInstaller::ensureHostingProductMappingSchema()`.
- `NtRcBillingEventManager::ensureSchema()` now calls `NtRcInstaller::ensureBillingEventSchema()`.

Table creation SQL is intentionally limited to installer/schema migration code.

## TODO

- Verify current ResellerClub SSL renew endpoint parameter contract before enabling `renewSsl()`.
- Run real PrestaShop 1.7, 8, and 9 smoke tests with module install/upgrade.
- Run ResellerClub sandbox/live-credential tests for verified SSL endpoints.
- Design secure transient/encrypted CSR handling before exposing automated customer reissue/enroll flows.

## Known Risks

- PHP CLI and real PrestaShop runtime tests were not available in this workspace.
- ResellerClub SSL renew remains a controlled TODO until official current parameter contract is verified.
- Raw certificate delivery still needs a secure download design; adapter sanitizes certificate-like response fields.

## Last Test

- Static repository scan for duplicate managers/providers/queue/monitoring/notification/pricing/billing/provisioning.
- Static check that runtime table creation SQL is limited to `NtRcInstaller`.
- Static check that `curl_exec` remains isolated in `NtRcApiClient`.
- Static check that `Mail::Send` remains notification-queue based.
- `git diff --check` was run.
- PHP lint could not be run because PHP CLI is not available in this workspace.

## Last Documentation Update

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/production/PRODUCTION_READINESS.md`
- `docs/testing/SANDBOX_PROVIDER_TESTS.md`
- `docs/testing/REGRESSION_TEST_PLAN.md`
- `docs/architecture/19_PROVIDER_SANDBOX_PRODUCTION_READINESS.md`
- `docs/database-schema.md`
- `prestashop/ntresellerclub/docs/API_CONTRACT_RULES.md`
- `prestashop/ntresellerclub/docs/DATABASE_SCHEMA.md`
- `prestashop/ntresellerclub/docs/ROADMAP.md`
