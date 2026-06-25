# Engine 10 - Renewal / Service Lifecycle Notification Wiring

## Purpose

Engine 10 connects the backend notification infrastructure from Engine 09 to real renewal and service lifecycle events.

The rule remains unchanged: no renewal, provisioning, provider, queue, or status flow sends mail directly. Every customer/admin message is inserted into `ntresellerclub_notification_queue` and cron sends pending rows through `NtRcNotificationQueueManager`.

## Flow

```text
Renewal scan / Domain provisioning success / Service status change
  -> NtRcNotificationEngine
  -> NtRcNotificationQueueManager::enqueue()
  -> ntresellerclub_notification_queue
  -> Cron notification batch
  -> PrestaShop Mail::Send
```

## Renewal Reminder Wiring

`NtRcRenewalManager::scan()` now checks domain services with:

- `service_type` in `domain`, `tr_domain`
- `status` in `active`, `ready`
- `expiry_date` matching 30, 15, 7, or 1 day from the current date

It no longer calls `Mail::Send` and no longer writes reminder state to `ntresellerclub_notice`. It calls `NtRcNotificationEngine::enqueueExpiryNotification()` instead.

Dedupe key format:

```text
service_expiry:{template_key}:{id_service}:{expiry_date}
```

This keeps repeated cron runs from creating duplicate pending/sent expiry notifications.

## Domain Provisioning Success Wiring

`NtRcOperationQueueProcessor` queues customer notifications after successful domain queue actions:

| Queue Action | Template Key |
|---|---|
| register | `domain_registered` |
| transfer | `domain_transfer_started` |
| renew | `domain_renewed` |

The notification enqueue is best-effort. If notification creation fails, the provider operation remains successful and the service update / queue done flow is not rolled back.

Dedupe key format:

```text
domain_lifecycle:{template_key}:{id_service}:{provider_order_or_service_or_queue_id}
```

## Service Status Wiring

`NtRcServiceRepository::updateStatus()` now queues lifecycle notifications when status changes to:

| Status | Template Key |
|---|---|
| suspended | `service_suspended` |
| expired | `service_expired` |

The repository does not send mail. It only creates notification queue rows through `NtRcNotificationEngine::enqueueServiceNotification()`.

Dedupe key format:

```text
service_status:{template_key}:{id_service}:{expiry_date_or_day}
```

## Security

- Provider responses remain sanitized before queue response storage.
- Notification variables, subject, body, retry errors, and notification logs are sanitized by Engine 09 classes.
- `api-key`, `api_key`, `auth-code`, `auth_code`, `passwd`, `password`, `token`, `credential`, and raw provider request data must not be written into mail/log output.

## Compatibility

- Existing `renewal_reminder` mail templates remain in the repository for backward compatibility, but Engine 10 does not use them.
- Existing `ntresellerclub_notice` table remains untouched for legacy installs, but Engine 10 does not write new renewal notices there.
- RuntimeGuard and cron batch limits stay in place.

## Test Targets

- Renewal scan queues `domain_expiring_30`, `domain_expiring_15`, `domain_expiring_7`, and `domain_expiring_1` without direct mail.
- Re-running cron for the same expiry day returns dedupe instead of duplicate rows.
- Successful register queues `domain_registered`.
- Successful transfer queues `domain_transfer_started`.
- Successful renew queues `domain_renewed`.
- `updateStatus($idService, 'suspended')` queues `service_suspended`.
- `updateStatus($idService, 'expired')` queues `service_expired`.
- Notification enqueue failure does not mark provider queue failed.
