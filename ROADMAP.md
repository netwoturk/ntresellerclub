# NetwoTurk ntresellerclub Roadmap

This root roadmap is the repository-level continuation file for Codex engine work. Module-specific historical notes remain under `prestashop/ntresellerclub/docs/`.

## Current Engine Chain

| Engine | Name | Status |
|---|---|---|
| 01 | Core | Completed baseline |
| 02 | Provider | Completed baseline |
| 03 | Queue | Completed baseline |
| 04 | Runtime | Completed baseline |
| 05 | Domain Provisioning | Completed in `codex/phase-07-domain-provisioning-engine` |
| 08 | Monitoring & Health | In progress in `codex/engine-08-monitoring-health` |

## Engine 08 - Monitoring & Health

### Scope

- Provider health snapshots
- Runtime health snapshots
- Queue statistics
- Failed queue visibility
- Memory and batch-limit tracking
- Cron execution marker
- Provider daily statistics

### Acceptance Criteria

- Backend services only; no admin template in this engine.
- Cron runs monitoring after renewal, pending provisioning, operation queue, and optional price sync work.
- Monitoring does not call provider APIs.
- Monitoring writes to schema-guarded tables.
- Credential-like fields are not logged or persisted in raw form.
- RuntimeGuard and batch limit behavior remains intact.

## Next Engines

| Engine | Name | Notes |
|---|---|---|
| 06 | Hosting | ResellerClub-only hosting provisioning |
| 07 | SSL | ResellerClub-only SSL provisioning |
| 09 | Notification | Service/customer/admin notification workflows |
| 10 | Renewal | Renewal automation expansion |
| 11 | Billing | Billing and invoice integration |
| 12 | Webhook | Provider webhook ingestion |
| 13 | Reporting | Admin/customer reports |
| 14 | Statistics | Advanced statistics and dashboards |
| 15 | Customer Dashboard | Customer service panel expansion |
| 16 | Admin Dashboard | Admin monitoring and operations UI |
| 17 | Security | Hardening and sensitive-data review |
| 18 | Production Hardening | Production readiness pass |
