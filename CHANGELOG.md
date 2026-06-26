# Changelog

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
