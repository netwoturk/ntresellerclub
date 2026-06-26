# Engine 14 - SSL Provisioning Engine

Branch: `codex/engine-14-ssl-provisioning`

## Purpose

Engine 14 adds backend-only SSL provisioning infrastructure. SSL is ResellerClub-only and remains queue/cron based. No admin UI is introduced.

## Flow

```text
Paid PrestaShop order
  -> NtRcOrderOrchestrator
  -> NtRcSslManager
  -> ntresellerclub_service service_type=ssl
  -> ntresellerclub_operation_queue action=ssl/*
  -> cron NtRcBillingOperationQueueProcessor
  -> NtRcSslOperationQueueProcessor
  -> NtRcResellerClubSslAdapter
```

The order hook does not call provider APIs. SSL operations run only from queue processors.

## Mapping

New table: `ntresellerclub_ssl_product_mapping`

- `id_product`
- `provider_code` fixed to `resellerclub`
- `provider_product_id`
- `billing_cycle`
- `currency`
- `active`

Pricing remains Engine 11 based through `NtRcPricingManager`; no new pricing engine is added.

## Queue Actions

Allowed SSL actions:

- `ssl/create`
- `ssl/renew`
- `ssl/reissue`
- `ssl/cancel`
- `ssl/details`
- `ssl/download`

DomainNameAPI has no SSL contract and cannot enqueue SSL work.

## Provider Adapter

`NtRcResellerClubSslAdapter` intentionally does not call real endpoints until official ResellerClub SSL endpoint/resource/action details are verified.

Until then it returns a controlled failure response with TODO metadata. This keeps production behavior safe and retryable without inventing API calls.

## Lifecycle

SSL lifecycle statuses use the existing service status model:

- `pending`
- `provisioning`
- `active`
- `renewal_due`
- `payment_required`
- `expired`
- `cancelled`
- `error`

Successful SSL actions can update:

- `provider_service_id`
- `provider_order_id`
- `ssl_certificate_number`
- `expiry_date`
- `status`

Raw certificate material is not stored.

## Billing

Engine 13 billing rules remain active. SSL renew does not create provider queue work until payment is confirmed. Without payment the service is set to `payment_required`, customer notification is queued, and billing event `renewal_payment_required` is recorded.

Provider credit shortages are still handled by `NtRcBillingOperationQueueProcessor`.

## Notifications

Notification queue template keys:

- `ssl_created`
- `ssl_renewed`
- `ssl_expired`
- `ssl_reissue_required`
- `payment_required`

No direct mail is sent from SSL manager, adapter, or queue processor.

## Monitoring

`NtRcSslMonitoring::summary()` exposes:

- `ssl_active_count`
- `ssl_failed_queue`
- `ssl_pending_provisioning`
- `ssl_expiring_count`

`NtRcStatisticsEngine::sslSummary()` and dashboard `ssl` summary expose the backend metrics.

## Security

Never log or persist:

- `api-key`
- `password`
- `credential`
- `csr`
- private key
- raw certificate
- `token`
- `auth-code`

Only safe IDs, statuses, provider codes, service type, domain name, and sanitized messages may be stored.

## TODO

- Verify official ResellerClub SSL endpoints and required payloads.
- Complete real adapter execution after verification.
- Connect SSL mapping management to a future Admin Operations UI.
