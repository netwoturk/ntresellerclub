# Changelog

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
