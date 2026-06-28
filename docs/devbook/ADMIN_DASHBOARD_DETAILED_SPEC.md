# Admin Dashboard Detailed Spec

Branch: `codex/v1-admin-dashboard-data-binding`

## Goal

The admin dashboard is the first real data-bound screen on top of the Engine 17 PrestaShop admin framework. It reads existing backend state only. It does not introduce a new engine, a new data architecture, or any provider API call.

## Data Provider

`NtRcAdminDashboardDataProvider` is the dashboard data source.

It returns:

- `kpis`
- `provider_health`
- `queue`
- `runtime`
- `service_overview`
- `failed_operations`
- `notifications`
- `quick_actions`

The provider may read existing DB tables and existing summary classes, including:

- `NtRcStatisticsEngine`
- `NtRcBillingMonitoring`
- `NtRcSslMonitoring`
- `NtRcRuntimeGuard`
- monitoring tables written by `NtRcMonitoringEngine`
- notification queue tables written by `NtRcNotificationQueueManager`

## KPI Metrics

Dashboard KPIs:

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

Provider health cards read the latest local monitoring snapshot for:

- ResellerClub
- DomainNameAPI

Displayed fields:

- status
- last_error
- checked_at

Provider health checks stay cron/monitoring-owned. Opening the dashboard must not call ResellerClub or DomainNameAPI.

## Queue Summary

Queue summary reads the existing operation queue table.

Displayed fields:

- pending
- processing
- done today
- failed
- retry count

## Runtime Summary

Runtime summary reads current runtime values plus the latest stored runtime snapshot.

Displayed fields:

- memory limit
- current memory
- peak memory
- cron last run
- batch limit

## Service Overview

Service overview groups existing `ntresellerclub_service` rows for:

- domain
- tr_domain
- hosting
- ssl

The table summarizes status counts with lightweight aggregate queries.

## Failed Operations

Failed operations lists the latest 10 local operation queue records with status:

- `failed`
- `provider_credit_required`

Displayed fields are limited to safe operational metadata:

- queue id
- provider
- service type
- action
- status
- retry count
- sanitized last error
- updated at

Payload and response JSON must not be displayed.

## Notification Summary

Notification summary reads the existing notification queue.

Displayed fields:

- pending
- sent today
- failed
- sanitized last_error

## Quick Actions

Dashboard quick actions link to existing admin sections:

- Queue
- Monitoring
- Pricing
- Settings

Links are generated through PrestaShop admin link helpers.

## Security Rules

The dashboard must not display:

- credential
- api-key
- token
- password
- auth-code
- private key
- CSR
- raw certificate
- provider payload JSON
- provider response JSON

Displayed error strings must be sanitized and then escaped by shared admin helpers.

## Performance Rules

- No provider API call on dashboard open.
- No queue processing on dashboard open.
- No notification sending on dashboard open.
- No large SQL scans or joins.
- Aggregate reads must stay grouped by indexed/status columns where possible.
- Failed operations are capped at 10 rows.

## Compatibility

The dashboard remains compatible with PrestaShop 1.7, 8, and 9 conventions by keeping:

- `ModuleAdminController`
- `Context::link->getAdminLink()`
- `Tools::safeOutput()`
- existing module installer-owned schema guards
