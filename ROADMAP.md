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
| 08 | Monitoring & Health | Completed in `codex/engine-08-monitoring-health` |
| 09 | Notification & Mail | Completed in `codex/engine-09-notification-mail` |
| 10 | Renewal / Service Lifecycle Notification Wiring | Completed in `codex/engine-10-renewal-service-lifecycle-notification` |

## Engine 09 - Notification & Mail

### Scope

- Central notification template registry
- TR/EN/DE/FR/ES/IT default template seed
- Customer, admin, and technical-admin recipient types
- Notification queue with pending/processing/sent/failed/cancelled statuses
- Retry fields and sanitized last error tracking
- PrestaShop `Mail::Send` wrapper through module mail templates
- Cron-based batch sending with RuntimeGuard limits
- Monitoring integration for failed queue and provider health warnings
- Service expiry notification preparation for renewal flows

### Acceptance Criteria

- Mail is never sent directly from provisioning, provider, queue, or monitoring logic.
- Events are inserted into `ntresellerclub_notification_queue` first.
- Cron processes pending notifications with batch limits.
- Credentials, auth codes, tokens, raw requests, and passwords are sanitized from body/log output.
- No admin UI/template is included in this engine.

## Engine 10 - Renewal / Service Lifecycle Notification Wiring

### Scope

- Route renewal reminders through `NtRcNotificationEngine` instead of direct `Mail::Send`.
- Reuse notification queue dedupe for 30/15/7/1 day domain expiry events.
- Queue customer notifications after successful domain register, transfer, and renew queue actions.
- Queue service lifecycle notifications when service status changes to suspended or expired.
- Keep provider queue success/retry behavior independent from notification enqueue failures.
- Document legacy `ntresellerclub_notice` as unused by the new renewal notification flow.

### Acceptance Criteria

- `NtRcRenewalManager` does not call `Mail::Send` directly.
- Domain lifecycle events use `domain_registered`, `domain_transfer_started`, and `domain_renewed` template keys.
- Suspended/expired service notifications use `service_suspended` and `service_expired` template keys.
- Notification failures are sanitized and warning-logged without rolling back provider success.
- RuntimeGuard and batch limits remain unchanged.

## Next Engines

| Engine | Name | Notes |
|---|---|---|
| 06 | Hosting | ResellerClub-only hosting provisioning |
| 07 | SSL | ResellerClub-only SSL provisioning |
| 11 | Billing | Billing, payment-required notification, and invoice integration |
| 12 | Webhook | Provider webhook ingestion |
| 13 | Reporting | Admin/customer reports |
| 14 | Statistics | Advanced statistics and dashboards |
| 15 | Customer Dashboard | Customer service panel expansion |
| 16 | Admin Dashboard | Admin monitoring and operations UI |
| 17 | Security | Hardening and sensitive-data review |
| 18 | Production Hardening | Production readiness pass |
