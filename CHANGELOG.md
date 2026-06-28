# Changelog

## 2026-06-28 - Task 01 API Settings & Connection Test

Branch: `codex/task-01-api-settings-connection-test`

### Added

- Added working API settings UI to `AdminNtRcSettingsController`.
- Added ResellerClub settings for enabled state, sandbox/live mode, auth user id, API key, optional reseller id, and language.
- Added DomainNameAPI settings for enabled state, sandbox/live mode, username, and password/API credential.
- Added explicit ResellerClub and DomainNameAPI connection test buttons.
- Added connection test result rendering with success/failed, sanitized last error, and checked timestamp.
- Added `docs/devbook/ADMIN_SCREEN_MAP.md`.

### Changed

- Settings save now writes provider API settings through PrestaShop `Configuration`.
- Connection tests write provider health snapshots to `ntresellerclub_provider_health` when possible.
- Legacy module configuration form no longer renders stored API key/password values into password input values.

### Security

- API key and password fields are blank on render with masked placeholders.
- Empty or masked secret submissions keep the existing stored Configuration value.
- Provider credentials are not logged or displayed in connection test errors.
- Provider API calls are limited to explicit connection test submissions; dashboard opening remains DB/snapshot-only.

### Performance

- Settings page render performs local Configuration and latest provider health reads only.
- Connection tests use existing provider/client classes and execute only one read-only provider check per button submit.

## 2026-06-28 - V1 Admin Dashboard Data Binding

Branch: `codex/v1-admin-dashboard-data-binding`

### Added

- Bound `AdminNtRcDashboardController` output to real `NtRcAdminDashboardDataProvider` data.
- Added dashboard KPIs for active domains, active TR domains, active hosting, active SSL, pending/failed queue, payment required, provider credit required, and notification pending/failed counts.
- Added provider health output for ResellerClub and DomainNameAPI from latest monitoring snapshots.
- Added queue, runtime, service overview, failed operations, notification summary, and quick action dashboard sections.
- Added `docs/devbook/ADMIN_DASHBOARD_DETAILED_SPEC.md`.

### Changed

- `NtRcAdminDashboardDataProvider` now returns a full dashboard data model from existing DB, monitoring, queue, billing, pricing-adjacent service, and notification snapshots.
- `NtRcAdminBaseController` now renders dashboard cards and tables instead of the Engine 17 foundation-only skeleton.

### Security

- Dashboard rendering does not expose credentials, api keys, tokens, passwords, auth codes, payloads, raw responses, CSR/private-key, or raw certificate data.
- Displayed error text is sanitized before output and still escaped by the shared admin widget/theme helpers.

### Performance

- Dashboard opening performs only local lightweight aggregate reads and latest-record lookups.
- Provider health is read from stored monitoring snapshots; no provider API check is executed on page load.
- Failed operation output is capped to the latest 10 records.

## 2026-06-26 - Engine 17 PrestaShop Admin Framework

Branch: `codex/engine-17-admin-framework`

### Added

- Added shared admin base controller through `NtRcAdminBaseController`.
- Added central admin navigation builder for NetwoTurk Hosting menu and section metadata.
- Added shared admin layout helper with header, sidebar, content, and footer shell.
- Added widget helper for KPI cards, tables, alerts, status badges, and statistic tiles.
- Added admin theme helper and dark/light-compatible CSS.
- Added dashboard data provider interface and dashboard provider using existing backend summaries only.
- Added thin PrestaShop admin controllers for Dashboard, Domains, TR Domains, Hosting, SSL, Queue, Billing, Monitoring, Notifications, Pricing, BTK CSV, Logs, Settings, and License.
- Added installer-managed admin tab registration/unregistration.
- Added `docs/architecture/20_ADMIN_FRAMEWORK.md` and `docs/devbook/ADMIN_FRAMEWORK.md`.

### Changed

- Existing `AdminNtRcSslController` now extends the shared admin base controller instead of duplicating layout behavior.
- Legacy admin API test button now runs local readiness checks instead of calling a provider endpoint.

### Security

- Shared helpers escape admin output through `Tools::safeOutput`.
- Admin forms keep PrestaShop admin token/CSRF conventions.
- Framework pages do not execute provider API calls.

## 2026-06-26 - Engine 16 Provider Sandbox & Production Readiness

Branch: `codex/engine-16-provider-sandbox-production-readiness`

### Added

- Added `NtRcProductionReadinessVerifier` for no-network backend readiness checks.
- Added production readiness documentation in `docs/production/PRODUCTION_READINESS.md`.
- Added sandbox provider test documentation in `docs/testing/SANDBOX_PROVIDER_TESTS.md`.
- Added regression test plan in `docs/testing/REGRESSION_TEST_PLAN.md`.
- Added `docs/architecture/19_PROVIDER_SANDBOX_PRODUCTION_READINESS.md`.

### Changed

- `NtRcHostingProductMappingManager::ensureSchema()` now delegates schema work to `NtRcInstaller::ensureHostingProductMappingSchema()`.
- `NtRcBillingEventManager::ensureSchema()` now delegates schema work to `NtRcInstaller::ensureBillingEventSchema()`.
- Production readiness docs now make installer-owned schema creation, queue-driven heavy work, notification queue mail, and provider contract guard behavior explicit.

### Fixed

- Removed direct manager-owned runtime table creation SQL from hosting mapping and billing event managers.
- Updated adapter/language architecture docs to require sanitized provider technical errors instead of raw error storage.

### TODO

- Run real PrestaShop 1.7/8/9 install and upgrade smoke tests.
- Run ResellerClub sandbox checks with real sandbox credentials.
- Verify the current official ResellerClub SSL renew parameter contract before enabling `renewSsl()`.

## 2026-06-26 - Engine 15 SSL Endpoint Verification + Admin Mapping Backend

Branch: `codex/engine-15-ssl-endpoint-verification-admin-mapping`

### Added

- Added verified ResellerClub SSL API wiring for add, details, reissue, delete/cancel, certificate details, and validation-status reads.
- Added `ssl/validation_status` queue contract support.
- Added SSL mapping admin backend skeleton through `NtRcSslMappingAdminRenderer`, `AdminNtRcSslController`, and `views/templates/admin/ssl_mapping.tpl`.
- Added `provider_credit_required` notification template key.
- Added `docs/architecture/18_SSL_ENDPOINT_VERIFICATION_ADMIN_MAPPING.md`.

### Changed

- Extended `ntresellerclub_ssl_product_mapping` with `ssl_product_type`, `cost_price`, and `sale_price`.
- `NtRcSslProductMappingManager` now syncs mapping cost/sale data into Engine 11 `NtRcPricingManager`.
- `NtRcSslMonitoring::summary()` now exposes `active_ssl_count`, `pending_ssl_queue`, `failed_ssl_queue`, `ssl_expiring_count`, and `ssl_provider_credit_required_count`.
- SSL API contract docs now distinguish verified endpoints from controlled TODO actions.

### Fixed

- DomainNameAPI remains blocked from all SSL queue actions, including `ssl/validation_status`.
- SSL adapter sanitizes CSR/private-key/certificate-like fields from returned data and failure payloads.

### TODO

- `renewSsl()` remains controlled TODO until the current ResellerClub SSL renew parameter contract is verified from official help material.
- Secure transient/encrypted CSR handling is required before customer-facing automated reissue/enroll flows are exposed.

## 2026-06-26 - Engine 14 SSL Provisioning

Branch: `codex/engine-14-ssl-provisioning`

### Added

- Added `NtRcSslProductMappingManager` and `ntresellerclub_ssl_product_mapping` for PrestaShop product to ResellerClub SSL product mapping.
- Added `NtRcSslManager` for SSL create, renew, reissue, cancel, details, and download queue orchestration.
- Added `NtRcSslOperationQueueProcessor` and `NtRcResellerClubSslAdapter`.
- Added SSL monitoring metrics through `NtRcSslMonitoring`, `NtRcStatisticsEngine::sslSummary()`, and dashboard `ssl` summary output.
- Added `docs/architecture/17_SSL_PROVISIONING_ENGINE.md`.

### Changed

- `NtRcOrderOrchestrator` now routes mapped SSL products through `NtRcSslManager`.
- `NtRcBillingOperationQueueProcessor` now extends the SSL-aware queue processor path.
- API contract now uses namespaced SSL queue actions: `ssl/create`, `ssl/renew`, `ssl/reissue`, `ssl/cancel`, `ssl/details`, and `ssl/download`.
- Notification templates now include `ssl_expired` and `ssl_reissue_required`.
- Service schema now includes safe `ssl_certificate_number` storage.

### Fixed

- DomainNameAPI remains blocked for SSL through contract guard.
- SSL renew does not create provider API queue before payment confirmation.
- ResellerClub SSL adapter does not call unverified endpoints and sanitizes CSR/private-key/certificate-like fields.

### TODO

- Real ResellerClub SSL endpoint execution remains TODO until official endpoint/resource/action details are verified.

## 2026-06-26 - Engine 13 Billing & Order Orchestrator

Branch: `codex/engine-13-billing-order-orchestrator`

### Added

- Added `NtRcOrderOrchestrator` as the central paid-order router for domain, TR domain, hosting, and SSL provisioning queues.
- Added `NtRcBillingEventManager` and `ntresellerclub_billing_event` for sanitized billing/order event history.
- Added `NtRcBillingOperationQueueProcessor` to mark provider balance errors as `provider_credit_required`.
- Added `NtRcBillingMonitoring` and dashboard/statistics billing summaries for backend-readable monitoring metrics.
- Added `docs/architecture/16_BILLING_ORDER_ORCHESTRATOR.md`.

### Changed

- `NtRcProvisioning` now delegates order processing to the orchestrator.
- Cron now uses the billing-aware queue processor.
- `NtRcServiceStatus` includes `provider_credit_required`.
- `NtRcOperationQueueManager::retryFailed()` now accepts `provider_credit_required` queues for post-top-up retry.
- Domain and hosting renew flows now record `renewal_payment_required` billing events when payment is missing.
- Installer and SQL install/uninstall paths now include the billing event table.

### Fixed

- Provider queue creation is blocked for unpaid, cancelled, refunded, failed, payment-error, and chargeback order states.
- Duplicate provisioning attempts are skipped and recorded instead of creating another service/queue.
- Provider credit shortage no longer maps to customer payment failure.
- Domain renew now follows the same no-payment/no-provider-queue rule as hosting renew.

### TODO

- Real SSL provider execution remains TODO until official ResellerClub SSL endpoint details are verified.
- Future Admin Operations UI should expose provider-credit retry actions.

## 2026-06-25 - Engine 12 Hosting Provisioning

Branch: `codex/engine-12-hosting-provisioning`

### Added

- Added `NtRcHostingProductMappingManager` for PrestaShop product to ResellerClub hosting package mapping.
- Added `NtRcHostingManager` for hosting create, renew, suspend, and unsuspend queue orchestration.
- Added `ntresellerclub_hosting_product_mapping` schema to installer and SQL install/uninstall files.
- Added queue actions `hosting/create`, `hosting/renew`, `hosting/suspend`, and `hosting/unsuspend`.
- Added backend monitoring metrics for active hosting count, failed hosting queue, and pending hosting provisioning.
- Added `docs/architecture/15_HOSTING_PROVISIONING_ENGINE.md`.

### Changed

- `NtRcProvisioning` now detects mapped hosting products and creates hosting service + queue records instead of direct API calls.
- `NtRcOperationQueueProcessor` now dispatches hosting queue actions and updates hosting service lifecycle state after successful provider responses.
- `NtRcServiceRepository` can create provisioning hosting services and emits `payment_required` lifecycle notifications.
- `NtRcServiceStatus` now includes Engine 12 hosting lifecycle statuses.
- API contract and database documentation now describe ResellerClub-only hosting rules.

### Fixed

- DomainNameAPI remains blocked for hosting through contract guard.
- Hosting renew does not create provider API queue before payment confirmation.
- ResellerClub hosting adapter sanitizes token/credential-like fields.

### TODO

- ResellerClub hosting create/renew/suspend/unsuspend/details endpoints are not implemented until official endpoint/resource/action details are verified.
