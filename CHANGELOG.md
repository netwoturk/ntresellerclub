# Changelog

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
