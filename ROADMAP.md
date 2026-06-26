# NetwoTurk ntresellerclub Roadmap

This root roadmap is the repository-level continuation file for Codex engine work.

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
| 11 | Pricing & Currency Finalization | Completed in `codex/engine-11-pricing-currency-finalization` |
| Feature | BTK CSV Reporting Premium | Completed in `codex/feature-btk-csv-reporting` |
| 12 | Hosting Provisioning | Completed in `codex/engine-12-hosting-provisioning` |
| 13 | Billing / Order Orchestrator | Completed in `codex/engine-13-billing-order-orchestrator` |
| 14 | SSL Provisioning | Completed in `codex/engine-14-ssl-provisioning` |
| 15 | SSL Endpoint Verification + Admin Mapping Backend | Completed in `codex/engine-15-ssl-endpoint-verification-admin-mapping` |
| 16 | Provider Sandbox & Production Readiness | Completed in `codex/engine-16-provider-sandbox-production-readiness` |
| 17 | PrestaShop Admin Framework | Completed in `codex/engine-17-admin-framework` |
| V1 | Admin Dashboard Data Binding | Completed in `codex/v1-admin-dashboard-data-binding` |

## Engine 12 - Hosting Provisioning

### Scope

- ResellerClub-only hosting backend provisioning path.
- `ntresellerclub_hosting_product_mapping` table for PrestaShop product id to provider package mapping.
- Queue actions: `hosting/create`, `hosting/renew`, `hosting/suspend`, `hosting/unsuspend`.
- Hosting lifecycle statuses: pending, provisioning, active, renewal_due, payment_required, suspended, expired, cancelled, error.
- Success hooks update `ntresellerclub_service` and enqueue `hosting_created` / `hosting_renewed` notifications.
- Monitoring exposes active hosting count, failed hosting queue, and pending hosting provisioning.
- ResellerClub hosting endpoints remain TODO until official endpoint/resource/action details are verified.

### Acceptance Criteria

- DomainNameAPI cannot pass hosting contract guard.
- Order provisioning creates hosting service + queue instead of direct API call.
- Renew does not call provider API before payment confirmation.
- Pricing remains manual/mapping based and does not add a ResellerClub price API.
- No admin UI is introduced.

## Next Engines

| Engine | Name | Notes |
|---|---|---|
| 18 | Queue & Monitoring Admin Screens | Operations UI using the admin framework |
| 19 | Billing & Notification Admin Screens | Billing events and mail queue visibility |
| 20 | SSL Renew + Secure CSR Flow | Verify renew contract and design transient/encrypted CSR handling |
| 21 | Webhook | Provider webhook ingestion |
| 22 | Customer Dashboard | Customer service panel expansion |
| 23 | Security | Deeper hardening and sensitive-data review |
