# Engine 09 - Notification & Mail Engine

## Purpose

Notification & Mail Engine provides a central backend service for customer, admin, and technical-admin notifications. It covers domain, hosting, SSL, queue, renewal, payment, provider health, and service lifecycle events.

No notification mail is sent directly from provisioning, queue, monitoring, or renewal flows. Events are first written to `ntresellerclub_notification_queue`, then cron sends pending rows in batches.

## Flow

```text
Domain / Hosting / SSL / Queue / Monitoring / Renewal event
  -> NtRcNotificationEngine
  -> NtRcNotificationQueueManager::enqueue()
  -> ntresellerclub_notification_queue
  -> Cron
  -> NtRcNotificationQueueManager::processPending()
  -> PrestaShop Mail::Send
  -> ntresellerclub_notification_log
```

Cron order after Engine 09:

```text
Renewal scan
Pending provisioning
Operation queue processor
DomainNameAPI price sync when enabled
Monitoring Engine
Notification & Mail Engine
```

## Classes

- `NtRcNotificationEngine`: event orchestration, monitoring integration, service-expiry notification preparation, admin/customer notification helpers.
- `NtRcMailTemplateManager`: template key registry, TR/EN/DE/FR/ES/IT default template seed, variable rendering, body sanitization.
- `NtRcNotificationQueueManager`: queue insert, pending batch read, lock, send, retry/failed handling, cancellation, notification log write.

## Tables

### `ntresellerclub_notification_template`

Stores language and recipient specific templates.

Important fields:

- `template_key`
- `lang_iso`
- `recipient_type`
- `subject`
- `body_html`
- `body_text`
- `is_active`

### `ntresellerclub_notification_queue`

Stores rendered mail jobs before delivery.

Important fields:

- `template_key`
- `lang_iso`
- `recipient_type`
- `id_customer`
- `id_service`
- `to_email`
- `subject`
- `body_html`
- `body_text`
- `status`
- `retry_count`
- `max_retries`
- `last_error`
- `dedupe_key`
- `lock_token`

Allowed statuses:

- `pending`
- `processing`
- `sent`
- `failed`
- `cancelled`

### `ntresellerclub_notification_log`

Stores delivery attempts and sanitized error messages. It does not store raw provider requests or credentials.

## Template Keys

- `domain_registered`
- `domain_transfer_started`
- `domain_renewed`
- `domain_expiring_30`
- `domain_expiring_15`
- `domain_expiring_7`
- `domain_expiring_1`
- `hosting_created`
- `hosting_renewed`
- `ssl_created`
- `ssl_renewed`
- `queue_failed_admin`
- `provider_down_admin`
- `payment_required`
- `service_suspended`
- `service_expired`

## Recipient Types

- `customer`
- `admin`
- `technical_admin`

`technical_admin` uses `NTRC_TECHNICAL_ADMIN_EMAIL` when configured and falls back to `PS_SHOP_EMAIL`.

## Multilanguage

Default templates are seeded for:

- TR
- EN
- DE
- FR
- ES
- IT

The mail wrapper files live under `mails/{lang}/notification.html` and `mails/{lang}/notification.txt`. Database templates provide the rendered subject/body.

## Monitoring Integration

Notification Engine can create admin notifications when:

- failed operation queue count is greater than zero, with a dedupe key based on date and failed count;
- provider health status is `warning`, `down`, or `error`, with a provider/date/status dedupe key.

## Renewal Integration

The engine prepares domain expiry notifications for 30, 15, 7, and 1 day before `expiry_date`. The dedupe key prevents repeated pending/sent notifications for the same service/template/date.

## Security

The engine sanitizes credential-like values in variables, subject, body, queue errors, and logs. The following must not appear in mail/log output:

- `api-key`
- `api_key`
- `auth-code`
- `auth_code`
- `passwd`
- `password`
- `token`
- `credential`
- raw provider requests

## Performance

- Sending is batch-limited by `NtRcRuntimeGuard::cronBatchLimit()`.
- Queue locking prevents duplicate sends by overlapping cron runs.
- No admin UI is included in this engine.
