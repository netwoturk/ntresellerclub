# Engine 15 - SSL Endpoint Verification + Admin Mapping Backend

Date: 2026-06-26

Branch: `codex/engine-15-ssl-endpoint-verification-admin-mapping`

## Goal

Engine 15 strengthens the SSL provisioning backend introduced in Engine 14 by verifying ResellerClub SSL API endpoints and preparing the admin/backend layer for SSL product mappings.

This engine does not build a full admin UI. It adds backend-safe mapping actions and a simple skeleton only.

## Provider Rule

SSL is ResellerClub-only.

DomainNameAPI is not an SSL provider in this module. It has no SSL queue action, adapter call, or API contract entry.

## Verified ResellerClub SSL Endpoints

The following ResellerClub SSL API paths were verified from ResellerClub help / KB material:

| Operation | Path | Method | Adapter |
|---|---|---|---|
| Add / purchase SSL order | `/api/sslcert/add.json` | POST | `createSsl()` |
| Enroll SSL order | `/api/sslcert/enroll.json` | POST | `enrollSsl()` |
| Reissue SSL order | `/api/sslcert/reissue.json` | POST | `reissueSsl()` |
| Order details | `/api/sslcert/details.json` | GET | `getSslDetails()` |
| Delete / cancel SSL order | `/api/sslcert/delete.json` | POST | `cancelSsl()` |
| Change verification method | `/api/sslcert/change-verification-method.json` | POST | future manager action |
| Validate CSR | `/api/sslcert/validate-csr.json` | POST | future manager action |
| Certificate details | `/api/sslcert/get-cert-details.json` | GET | `downloadSsl()` |

`getValidationStatus()` uses `details.json` because ResellerClub documents DCV/verification fields in the details response, including CNAME and HTTP verification parameters when action status waits for verification.

## Controlled TODO

`renewSsl()` remains a controlled TODO. A `sslcert/renew.xml` path was found in legacy/WebPro material, but a current ResellerClub help article with the complete parameter contract was not verified. The adapter therefore does not execute production renew API calls yet.

CSR-sensitive actions are also constrained:

- `reissueSsl()` and `enrollSsl()` require CSR.
- `NtRcSslManager` does not persist raw CSR/private key/certificate values in lifecycle queue options.
- A later engine must define encrypted/transient CSR input handling before automated customer reissue/enroll flows are exposed.

## Queue Contract

Allowed SSL queue actions for ResellerClub:

- `ssl/create`
- `ssl/renew`
- `ssl/reissue`
- `ssl/cancel`
- `ssl/details`
- `ssl/download`
- `ssl/validation_status`

DomainNameAPI rejects all SSL actions through `NtRcApiContractGuard`.

Provider API calls remain queue/cron/processor-only. Admin mapping actions never call ResellerClub.

## SSL Product Mapping Backend

`ntresellerclub_ssl_product_mapping` was extended with:

- `ssl_product_type`
- `cost_price`
- `sale_price`

The mapping manager writes sale/cost information into the existing Engine 11 pricing table through `NtRcPricingManager`. No new pricing engine was introduced.

Backend skeleton pieces:

- `NtRcSslMappingAdminRenderer`
- `AdminNtRcSslController`
- `views/templates/admin/ssl_mapping.tpl`

Supported backend actions:

- list mappings
- save mapping
- toggle active status

Token checks use PrestaShop admin tokens. Values are validated and sanitized through `Tools`, `Validate`, `pSQL`, and manager normalization.

## Billing

Engine 13 billing remains the integration point.

SSL renew queue creation is blocked when payment is not confirmed. The service is marked `payment_required`, a billing event is recorded, and notification queue is used.

Provider credit failures are marked as `provider_credit_required` and now use the `provider_credit_required` notification template key.

`createSsl()` requires a ResellerClub `customer-id`. `NtRcSslManager` reuses `NtRcProviderCustomerManager::ensure()` before creating the SSL service. If the provider customer is not ready yet, the customer queue is created/reused and SSL provisioning waits for a later order/provisioning retry instead of calling the provider with missing customer data.

## Notification

SSL notification keys:

- `ssl_created`
- `ssl_renewed`
- `ssl_expired`
- `ssl_reissue_required`
- `payment_required`
- `provider_credit_required`

No direct mail sending is introduced.

## Monitoring

`NtRcSslMonitoring::summary()` exposes:

- `active_ssl_count`
- `pending_ssl_queue`
- `failed_ssl_queue`
- `ssl_expiring_count`
- `ssl_provider_credit_required_count`

Backward-compatible Engine 14 keys are retained.

## Security

The following must not be logged or stored in queue responses:

- `api-key`
- `password`
- `credential`
- `token`
- `auth-code`
- `csr`
- `private_key`
- `private-key`
- `certificate`
- `certificate_raw`
- `cert_raw`

The SSL adapter removes raw provider response fields and sanitizes payload/error text before returning data to queue processors.

## Shared Hosting Compatibility

No provider API call runs from hooks or admin mapping screens.

Order flow:

```text
Paid order
  -> NtRcOrderOrchestrator
  -> NtRcSslManager
  -> ntresellerclub_operation_queue
  -> cron
  -> NtRcSslOperationQueueProcessor
  -> NtRcResellerClubSslAdapter
```
