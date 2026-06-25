# Engine 12 - Hosting Provisioning Engine

Branch: `codex/engine-12-hosting-provisioning`

## Purpose

Hosting sales and lifecycle infrastructure is prepared as ResellerClub-only backend infrastructure. DomainNameAPI is never used for hosting. Order validation does not call the provider API directly; it creates a service record and an operation queue item, then cron processes the queue.

## Provider Rule

| Service | Provider | Status |
|---|---|---|
| Hosting create | ResellerClub | Queue action: `hosting/create` |
| Hosting renew | ResellerClub | Queue action: `hosting/renew` |
| Hosting suspend | ResellerClub | Queue action: `hosting/suspend` |
| Hosting unsuspend | ResellerClub | Queue action: `hosting/unsuspend` |
| Hosting via DomainNameAPI | Forbidden | Contract guard rejects it |

## Mapping

New table: `ntresellerclub_hosting_product_mapping`

Fields:

- `id_product` - PrestaShop product id
- `provider_code` - fixed `resellerclub`
- `provider_product_id`
- `package_name`
- `billing_cycle`
- `cost_price`
- `sale_price`
- `currency`
- `active`

`NtRcHostingProductMappingManager` reads and writes mapping rows. Hosting pricing is manual/mapping based. No speculative ResellerClub price API was added.

## Order Flow

1. `hookActionValidateOrder` runs the existing `NtRcProvisioning` flow.
2. `NtRcHostingManager::maybeProvisionHosting()` checks active hosting mapping by PrestaShop product id.
3. If mapping exists, `ntresellerclub_service` gets `service_type=hosting` and `status=provisioning`.
4. `ntresellerclub_operation_queue` gets `provider_code=resellerclub`, `service_type=hosting`, `action=hosting/create`.
5. Cron uses `NtRcHostingOperationQueueProcessor`, which extends the existing operation queue processor without changing domain/customer behavior.

## Lifecycle

Supported hosting status values:

- `pending`
- `provisioning`
- `active`
- `renewal_due`
- `payment_required`
- `suspended`
- `expired`
- `cancelled`
- `error`

On successful create/renew responses, `provider_service_id`, `provider_order_id`, and `expiry_date` are saved when present. `hosting_created` and `hosting_renewed` notification queue records are prepared.

## Renew And Payment

`NtRcHostingManager::enqueueRenew()` does not create a provider queue item before payment confirmation. Without confirmed payment, the service becomes `payment_required` and the notification queue integration is ready.

## Suspend / Unsuspend

`hosting/suspend` and `hosting/unsuspend` queue actions are contract-guarded and processor-ready. The ResellerClub hosting endpoints were not verified in this repository, so the adapter does not make real API calls yet.

## ResellerClub Hosting Adapter TODO

`NtRcResellerClubHostingAdapter` intentionally returns controlled TODO responses for:

- `createHosting()`
- `renewHosting()`
- `suspendHosting()`
- `unsuspendHosting()`
- `getHostingDetails()`

TODO: Complete these methods only after official ResellerClub hosting resource/action path, HTTP method, required parameters, response fields, and lifecycle error codes are verified. Do not add speculative endpoints.

## Monitoring

`NtRcHostingMonitoring::summary()` exposes:

- `active_hosting_count`
- `failed_hosting_queue`
- `pending_hosting_provisioning`

## Security

Provider response and safe payload data sanitize `api-key`, `password`, `passwd`, `auth-code`, `token`, and `credential`-like fields. The adapter does not log raw request/response data.

## Test Scenarios

- A mapped PrestaShop hosting product creates a `provisioning` hosting service.
- The same order creates a `hosting/create` queue item with provider `resellerclub` and service type `hosting`.
- DomainNameAPI hosting queue attempts are rejected by contract guard.
- Renew without payment confirmation sets `payment_required` and does not create provider queue work.
- Successful provider responses update provider ids and expiry date.
- Until ResellerClub hosting endpoints are verified, the adapter returns a controlled TODO/failure response.
