# Engine 13 - Billing & Order Orchestrator

Branch: `codex/engine-13-billing-order-orchestrator`

## Purpose

Engine 13 centralizes PrestaShop order/payment state checks and routes paid order items into domain, TR domain, hosting, and SSL provisioning queues.

The customer pays only in PrestaShop. Provider operations use the NetwoTurk reseller/provider account balance. Provider credit shortage is an admin operation state, not a customer payment failure.

## Main Flow

```text
PrestaShop order hook
  -> NtRcProvisioning::processOrder()
  -> NtRcOrderOrchestrator
  -> payment state check
  -> product classification
  -> service duplicate guard
  -> service record
  -> operation queue
  -> cron processor
```

No provider API call is made from the order hook. Heavy work stays behind queue, cron, and processor layers.

## Order State Mapping

Provisioning is allowed only when the order state is paid/accepted:

- `payment accepted`
- `accepted`
- `paid`
- native PrestaShop `OrderState::$paid`

Provisioning is skipped for:

- `awaiting payment`
- `cancelled`
- `canceled`
- `refunded`
- `payment error`
- `failed`
- `chargeback`

Skipped orders create a billing event and admin notification, but no provider queue.

## Service Routing

| Service type | Provider | Queue action |
|---|---|---|
| `domain` | ResellerClub | existing domain register/transfer/renew action model |
| `tr_domain` | DomainNameAPI | existing TR domain register/transfer/renew action model |
| `hosting` | ResellerClub | `hosting/create` |
| `ssl` | ResellerClub | `create` under `service_type=ssl` |

DomainNameAPI must never be used for hosting or SSL.

## Duplicate Provisioning Guard

The orchestrator checks existing services before creating a new service/queue:

- `id_order`
- `id_product`
- `service_type`
- `domain_name`
- `provider_code`

Duplicate attempts are skipped, recorded as `duplicate_skipped`, and notify admin through the existing notification queue.

## Billing Event History

New table: `ntresellerclub_billing_event`

Supported event types include:

- `order_paid`
- `order_not_paid`
- `provisioning_queued`
- `duplicate_skipped`
- `payment_required`
- `provider_credit_required`
- `order_cancelled`
- `order_refunded`
- `provisioning_failed`
- `renewal_payment_required`

Metadata is sanitized before write. Raw provider request/response, API keys, passwords, tokens, credentials, auth codes, and card-like keys are not stored.

## Provider Credit Required

`NtRcBillingOperationQueueProcessor` extends the existing hosting/domain queue processor path and inspects failed provider errors for credit/balance shortage patterns.

When provider credit is insufficient:

- operation queue status becomes `provider_credit_required`
- related service status becomes `provider_credit_required`
- billing event `provider_credit_required` is recorded
- admin notification is queued
- customer order is not cancelled
- customer payment state is not changed

Admin adds provider credit and retries the failed/provider-credit queue from operations.

## Payment Required

Renewal or extra paid actions must not create provider API queues before PrestaShop payment is confirmed. Existing lifecycle code can set:

- service status `payment_required`
- notification `payment_required`
- billing event `payment_required`

## Monitoring

Backend-readable metrics are exposed through `NtRcBillingMonitoring::summary()` and `NtRcStatisticsEngine::billingSummary()`:

- `payment_required_count`
- `provider_credit_required_count`
- `billing_failed_count`
- `unpaid_renewal_count`
- `provisioning_queued_today`
- `order_skipped_count`
- `service_payment_required_count`
- `service_provider_credit_required_count`
- `queue_provider_credit_required_count`

`NtRcDashboard::summary()` exposes these under the `billing` key. No admin UI is introduced.

## Security

Billing events, notification variables, queue errors, and logs must stay sanitized. Do not persist:

- card data
- raw transaction response
- raw provider request
- `api-key`
- `password`
- `token`
- `credential`
- `auth-code`

Safe fields are IDs, provider/service types, safe statuses, safe messages, and sanitized metadata.

## TODO

- SSL provider adapter execution remains pending until verified ResellerClub SSL endpoint/resource/action details are available.
- Provider credit retry UX should be connected to the future Admin Operations UI.
- Renewal payment confirmation should be connected to the future billing/invoice engine once PrestaShop invoice/payment references are finalized.
