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
| 11 | Pricing & Currency Finalization | Completed in `codex/engine-11-pricing-currency-finalization` |

## Engine 11 - Pricing & Currency Finalization

### Scope

- Central pricing calculation through `NtRcPricingEngine`.
- Generic price mapping through `NtRcPricingManager` for domain, TR domain, hosting, and SSL rows.
- DomainNameAPI TR domain pricing strengthened without changing the cron/provider boundary.
- ResellerClub global domain, hosting, and SSL mapping placeholders prepared without speculative API endpoints.
- Manual USD rates generalized for TRY, EUR, GBP, and AZN.
- Margin models preserved: manual, percent, fixed, hybrid.
- Tax included/excluded calculation and currency-level future tax override support.
- Rounding modes: no_round, nearest_1, nearest_5, nearest_10, psychological_99.
- Standard calculation result format for downstream sales flows.
- Price and exchange-rate history write paths preserved.

### Acceptance Criteria

- Price calculation returns `cost_price`, `cost_currency`, `converted_cost`, `target_currency`, `margin_amount`, `tax_amount`, `sale_price_without_tax`, `sale_price_with_tax`, `rounding_mode`, and `final_sale_price`.
- DomainNameAPI price fetching stays cron/RuntimeGuard guarded.
- ResellerClub pricing infrastructure does not invent API endpoints.
- Existing TR price admin/backend paths remain backward compatible.
- No new admin UI is introduced.

## Previous Engines

| Engine | Name | Notes |
|---|---|---|
| 09 | Notification & Mail | Queue-based mail delivery and notification templates |
| 10 | Renewal / Service Lifecycle Notification Wiring | Renewal and lifecycle events connected to notification queue |

## Next Engines

| Engine | Name | Notes |
|---|---|---|
| 06 | Hosting | ResellerClub-only hosting provisioning |
| 07 | SSL | ResellerClub-only SSL provisioning |
| 12 | Billing | Billing, payment-required notification, and invoice integration |
| 13 | Webhook | Provider webhook ingestion |
| 14 | Reporting | Admin/customer reports |
| 15 | Statistics | Advanced statistics and dashboards |
| 16 | Customer Dashboard | Customer service panel expansion |
| 17 | Admin Dashboard | Admin monitoring and operations UI |
| 18 | Security | Hardening and sensitive-data review |
| 19 | Production Hardening | Production readiness pass |
