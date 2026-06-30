# NetwoTurk ntresellerclub Roadmap

This root roadmap tracks the current V1 recovery path.

## Current Chain

| Work | Status | Branch |
|---|---|---|
| Engine 17 - PrestaShop Admin Framework | Completed | `codex/engine-17-admin-framework` |
| Task 01 - API Settings & Connection Test | Completed | `codex/task-01-api-settings-connection-test` |
| Task 02 - Domain Search API Flow | Completed | `codex/task-02-domain-search-api-flow` |
| Task 03 - Domain Search Result To Cart Flow | Completed | `codex/task-03-domain-search-cart-flow` |
| Task 06 - Simple Admin UX Redesign V1 | Completed | `codex/task-06-simple-admin-ux-redesign-v1` |
| Task 08 - V1 Domain Flow Recovery | Completed | `codex/v1-recovery-task04-05-admin-ux` |

## Recovered V1 Scope

- Customer domain search page.
- Add-to-cart UI using existing `domainsearch` and `domaincart` endpoints.
- Product mapping readiness for global and TR domain products.
- Cart/order metadata validation before service and queue creation.
- Paid order to local service and queue creation through existing orchestrator flow.
- Customer My Services page.
- Read-only Admin Domains and Admin TR Domains visibility.
- Runtime smoke upgrade to module version `0.1.1`.

## Next Tasks

| Task | Name | Notes |
|---|---|---|
| Task 09 | Live PrestaShop Smoke Validation | Install/upgrade test on PrestaShop 1.7, 8, and 9. |
| Task 10 | Queue & Monitoring Admin Screens | Build dedicated operation visibility screens using the admin framework. |
| Task 11 | Billing & Notification Admin Screens | Add billing event and notification queue visibility. |
| Task 12 | Checkout UX Hardening | Add customer-facing validation for required domain metadata and contact requirements. |
