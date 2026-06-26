# Current Status

Date: 2026-06-26

## Last Work

Engine 13 - Billing & Order Orchestrator

## Last Branch

`codex/engine-13-billing-order-orchestrator`

## Completed

- Central order orchestration was added through `NtRcOrderOrchestrator`.
- `NtRcProvisioning` now delegates order hook work to the orchestrator.
- Provisioning starts only for paid/accepted PrestaShop order states.
- Unpaid, cancelled, refunded, failed, chargeback, and payment-error orders are skipped without provider queue creation.
- Domain, TR domain, hosting, and SSL products are classified through the same order flow.
- Duplicate provisioning is guarded by order, product, service type, domain, and provider.
- Billing event history was added with sanitized metadata.
- Provider credit shortage is treated as `provider_credit_required`, not as a customer payment failure.
- Cron now uses `NtRcBillingOperationQueueProcessor` to detect provider credit/balance errors.
- Provider-credit queues can be retried after provider balance is topped up.
- Domain and hosting renew helpers block provider queue creation until payment confirmation and record `renewal_payment_required`.
- Backend monitoring exposes billing/order metrics without adding admin UI.
- Installer, install SQL, and uninstall SQL include `ntresellerclub_billing_event`.

## Database Changes

New table:

- `ntresellerclub_billing_event`

Key fields:

- `id_order`
- `id_customer`
- `id_service`
- `provider_code`
- `service_type`
- `event_type`
- `event_status`
- `message`
- `metadata_json`
- `created_at`

## TODO

- Verify and implement real ResellerClub SSL endpoint/resource/action details before executing SSL provider calls.
- Connect provider-credit retry actions to the future Admin Operations UI.
- Connect renewal payment confirmation references to the future billing/invoice engine.

## Known Risks

- PHP runtime and real PrestaShop runtime tests were not available in this environment.
- Product classification depends on existing mapping/reference/domain fields; non-standard catalog data may need admin mapping.
- Provider credit detection uses sanitized error text pattern matching and may need provider-specific error-code refinement after live API responses are observed.

## Last Test

- New and changed PHP files passed a brace/parenthesis balance check.
- `php -l` could not be run because PHP CLI is not available in this workspace.
- Order state mapping, duplicate guard, provider-credit status path, and credential sanitization were statically reviewed.

## Last Documentation Update

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/architecture/16_BILLING_ORDER_ORCHESTRATOR.md`
- `prestashop/ntresellerclub/docs/API_CONTRACT_RULES.md`
- `prestashop/ntresellerclub/docs/DATABASE_SCHEMA.md`
