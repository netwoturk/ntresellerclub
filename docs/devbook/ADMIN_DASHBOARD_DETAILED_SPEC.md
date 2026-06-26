# Admin Dashboard Detailed Spec

Branch: `codex/v1-admin-dashboard-data-binding`

## Scope

The V1 admin dashboard binds the Engine 17 framework to existing backend snapshots. It does not introduce new business logic, new tables, or provider API calls.

## Data Source

`NtRcAdminDashboardDataProvider`

Allowed sources:

- `ntresellerclub_service`
- `ntresellerclub_operation_queue`
- `ntresellerclub_provider_health`
- `ntresellerclub_runtime_health`
- `ntresellerclub_notification_queue`
- existing `NtRcStatisticsEngine`
- existing billing/SSL/hosting monitoring summaries

Disallowed sources:

- ResellerClub API
- DomainNameAPI API
- provider adapters
- cron processors
- raw credential/payload output

## KPI Cards

The dashboard exposes:

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

## Provider Health

Provider health reads the latest local snapshot for:

- ResellerClub status
- DomainNameAPI status
- last error
- checked at

Last error text is sanitized before rendering.

## Queue Summary

The queue summary includes:

- pending
- processing
- done today
- failed
- retry count

## Runtime Summary

Runtime summary includes:

- memory limit
- current memory
- peak memory
- cron last run
- batch limit

## Service Overview

Service overview groups local services by:

- domain
- tr_domain
- hosting
- ssl

Each row shows total count and status-count summary.

## Failed Operations

The dashboard lists the latest 10 failed or provider-credit-required operation queue rows.

Rendered fields are sanitized and do not expose request payloads.

## Quick Actions

The dashboard links to:

- Queue
- Monitoring
- Pricing
- Settings

Links use PrestaShop admin link generation.
