# Changelog

## 2026-06-25 - Engine 11 Pricing & Currency Finalization

Branch: `codex/engine-11-pricing-currency-finalization`

### Added

- Added `NtRcPricingEngine` as the central calculation engine for cost conversion, margin, tax, rounding, and standard price result output.
- Added `NtRcPricingManager` as a generic mapping manager for domain, TR domain, hosting, and SSL pricing rows.
- Added ResellerClub mapping seed infrastructure for global domain, hosting, and SSL placeholders without adding provider API endpoints.
- Added `docs/architecture/14_PRICING_CURRENCY_ENGINE.md`.
- Added pricing schema guard columns: `target_currency`, `tax_rate`, `rounding_mode`, `created_at`, and `updated_at` on `ntresellerclub_price`.

### Changed

- `NtRcManualExchangeRate` now supports USD -> TRY/EUR/GBP/AZN and writes rate changes to exchange-rate history.
- `NtRcTrPriceCalculator` now delegates to `NtRcPricingEngine` while preserving backward-compatible result keys.
- `NtRcTrPriceManager` now uses `NtRcPricingManager` for DomainNameAPI TR domain rows.
- `NtRcDomainNameApiPriceSync` now relies on the central manager for price history writes to avoid duplicate history rows.
- Module install now seeds default USD rates and ResellerClub mapping placeholders.
- `install.sql`, `DATABASE_SCHEMA.md`, `API_CONTRACT_RULES.md`, `ROADMAP.md`, and `CURRENT_STATUS.md` now document Engine 11 rules.

### Fixed

- Pricing output is standardized for downstream sales flows.
- DomainNameAPI price sync keeps its cron/RuntimeGuard boundary while using the finalized pricing manager.
- Manual, percent, fixed, and hybrid margin calculations now share the same tax and rounding flow.

### Removed

- None.

## 2026-06-25 - Engine 10 Renewal / Service Lifecycle Notification Wiring

Branch: `codex/engine-10-renewal-service-lifecycle-notification`

### Added

- Added `docs/architecture/13_RENEWAL_SERVICE_LIFECYCLE_NOTIFICATION_WIRING.md`.
- Added `NtRcNotificationEngine::enqueueExpiryNotification()` as a reusable domain expiry notification helper.
- Added domain lifecycle notification enqueueing after successful register, transfer, and renew queue actions.
- Added service lifecycle notification enqueueing for suspended and expired status changes.

### Changed

- `NtRcRenewalManager` now queues expiry notifications through Notification Engine instead of sending `renewal_reminder` mail directly.
- `NtRcNotificationEngine::enqueueExpiryNotifications()` now reuses the central expiry helper and includes `tr_domain` services.
- Notification service variables now include service ID, service type, service status, provider order ID, and provider service ID.
- `NtRcOperationQueueProcessor` keeps provider queue success independent from notification enqueue failures.
- `NtRcServiceRepository::updateStatus()` now emits queue-based lifecycle notifications for customer-facing suspended/expired transitions.
- `ROADMAP.md`, `DATABASE_SCHEMA.md`, `API_CONTRACT_RULES.md`, and `CURRENT_STATUS.md` now document Engine 10 rules.

### Fixed

- Renewal scan no longer sends mail directly from a heavy cron path.
- Renewal reminders no longer depend on the legacy `ntresellerclub_notice` write path.
- Provider response sanitization now also removes token/credential-like fields in the operation queue processor.

### Removed

- Direct `Mail::Send` usage from `NtRcRenewalManager`.

## 2026-06-25 - Engine 09 Notification & Mail

Branch: `codex/engine-09-notification-mail`

### Added

- Added `NtRcNotificationEngine` for notification orchestration.
- Added `NtRcMailTemplateManager` for template registry, six-language defaults, and safe rendering.
- Added `NtRcNotificationQueueManager` for queued mail processing, locking, retry, cancellation, and logs.
- Added notification tables: `ntresellerclub_notification_template`, `ntresellerclub_notification_queue`, `ntresellerclub_notification_log`.
- Added module mail wrappers for TR, EN, DE, FR, ES, and IT: `notification.html` and `notification.txt`.
- Added `docs/architecture/12_NOTIFICATION_MAIL_ENGINE.md`.

### Changed

- Cron now runs Notification & Mail Engine after Monitoring Engine.
- Installer and `install.sql` now create notification tables.
- `DATABASE_SCHEMA.md`, `API_CONTRACT_RULES.md`, `ROADMAP.md`, and `CURRENT_STATUS.md` now document notification rules and schema.

### Fixed

- Mail body, subject, variables, retry errors, and notification logs are sanitized for credential-like values.
- Direct mail sending from heavy flows is avoided; mail delivery is queue-based and batch-limited.

### Removed

- None.

## 2026-06-25 - Engine 08 Monitoring & Health

Branch: `codex/engine-08-monitoring-health`

### Added

- Added backend monitoring orchestration with `NtRcMonitoringEngine`.
- Added runtime/provider health snapshots with `NtRcHealthChecker`.
- Added provider queue statistics aggregation with `NtRcStatisticsEngine`.
- Added `ntresellerclub_provider_health`, `ntresellerclub_runtime_health`, and `ntresellerclub_provider_statistics` tables.
- Added `docs/architecture/MONITORING_HEALTH_ENGINE.md`.
- Added root repository continuation docs: `ROADMAP.md`, `CURRENT_STATUS.md`, and `CHANGELOG.md`.

### Changed

- Cron now runs monitoring after renewal scan, pending provisioning, operation queue processing, and optional DomainNameAPI price sync.
- Installer now creates and guards monitoring tables.
- Database schema documentation now includes Monitoring & Health tables.

### Fixed

- Cron exception output now sanitizes credential-like values before returning JSON.

### Removed

- None.
