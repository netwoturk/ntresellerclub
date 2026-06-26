# Current Status

Date: 2026-06-26

## Last Work

V1 Admin Dashboard Data Binding

## Last Branch

`codex/v1-admin-dashboard-data-binding`

## Completed

- Bound the Engine 17 Dashboard screen to `NtRcAdminDashboardDataProvider`.
- Added real dashboard KPI data from local DB/backend summaries.
- Added provider health cards for ResellerClub and DomainNameAPI from local `ntresellerclub_provider_health` snapshots.
- Added queue summary, runtime summary, service overview, failed operations, notification summary, and quick action links.
- Added `docs/devbook/ADMIN_DASHBOARD_DETAILED_SPEC.md`.

## Connected KPIs

- `active_domain_count`
- `active_tr_domain_count`
- `active_hosting_count`
- `active_ssl_count`
- `pending_queue_count`
- `failed_queue_count`
- `payment_required_count`
- `provider_credit_required_count`
- `notification_pending_count`
- `notification_failed_count`

## Security

- Dashboard renders sanitized text only.
- Failed operation rows do not expose payload JSON or credential fields.
- Provider health `last_error` is sanitized.
- Provider API clients/adapters are not called during dashboard page load.

## Performance

- Dashboard uses small aggregate queries and `LIMIT 10` for failed operations.
- No cron processor, provider health check, or external API runs during page load.
- Existing monitoring, billing, queue, and service snapshot structures are reused.

## Database Changes

No database schema change.

## TODO

- Run real PrestaShop 1.7, 8, and 9 back-office smoke tests.
- Build Queue and Monitoring admin operation screens.
- Add richer dashboard filters/date ranges only if needed.

## Last Test

- Static scan verified dashboard/admin framework does not call `NtRcApiClient`, provider adapters, `curl_exec`, or `Mail::Send`.
- Static scan verified dashboard controller layer does not write SQL directly.
- `git diff --check` was run.
- PHP lint could not be run because PHP CLI is not available in this workspace.

## Last Documentation Update

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/architecture/20_ADMIN_FRAMEWORK.md`
- `docs/devbook/ADMIN_FRAMEWORK.md`
- `docs/devbook/ADMIN_DASHBOARD_DETAILED_SPEC.md`
