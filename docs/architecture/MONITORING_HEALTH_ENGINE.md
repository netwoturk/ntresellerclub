# Monitoring & Health Engine

## Purpose

Engine 08 adds backend-only observability for the ntresellerclub module. It records provider health, runtime health, and provider queue statistics without adding admin templates or synchronous provider API calls.

## Flow

```text
Cron Controller
  -> Renewal scan
  -> Pending provisioning
  -> Operation queue processor
  -> DomainNameAPI price sync when enabled
  -> NtRcMonitoringEngine
       -> NtRcHealthChecker
       -> NtRcStatisticsEngine
       -> health/statistics tables
```

## Classes

- `NtRcMonitoringEngine`: orchestration entrypoint for cron/backend monitoring snapshots.
- `NtRcHealthChecker`: records runtime, cron, queue, failed queue, and provider health signals.
- `NtRcStatisticsEngine`: aggregates provider queue statistics and daily metrics.

## Tables

- `ntresellerclub_provider_health`: provider enabled/licensed state, queue pending/failed counts, latest sanitized error, and response-time metric for the health snapshot.
- `ntresellerclub_runtime_health`: memory usage, memory peak, max execution time, batch limit, SAPI, queue counts, and last cron timestamp.
- `ntresellerclub_provider_statistics`: per-provider daily queue totals, done/failed/pending/processing counts, retry count, average retry, and last success/failure timestamps.

## Safety Rules

- Monitoring does not call ResellerClub or DomainNameAPI APIs.
- Monitoring does not log API credentials, tokens, passwords, auth codes, or raw requests.
- Cron remains batch-limited through `NtRcRuntimeGuard::cronBatchLimit()`.
- Heavy cron entrypoints continue to call `NtRcRuntimeGuard::beforeHeavyProcess()`.
- Failed queue information is stored as sanitized error text only.

## Admin UI

No admin template is included in this engine. Future admin dashboard work can read the three monitoring tables and the `NtRcMonitoringEngine` output.
