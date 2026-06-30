# Current Status

Date: 2026-06-30

## Last Work

Task 08 - V1 Domain Flow Recovery

## Last Branch

`codex/v1-recovery-task04-05-admin-ux`

## Completed

- Recovered the customer domain search page at `/module/ntresellerclub/domainsearchpage`.
- Added frontend domain search template, JavaScript, and CSS that call the existing `domainsearch` and `domaincart` endpoints.
- Restored add-to-cart UI for available domains with user-friendly messages and a `Sepete Git` link.
- Preserved and strengthened `NTRC_DOMAIN_PRODUCT_ID` and `NTRC_TR_DOMAIN_PRODUCT_ID` product mapping readiness.
- Domain cart responses now include stable error codes: `product_mapping_missing`, `unavailable`, `duplicate`, and `failed`.
- `NtRcCartDomain::getDomainsByCart()` now returns normalized order-orchestrator metadata.
- `NtRcOrderOrchestrator` validates `domain_name`, `tld`, `provider_code`, `service_type`, `years`, `id_product`, `price_snapshot`, and `currency` before service/queue creation.
- Invalid cart metadata records a `cart_metadata_invalid` billing event and admin notification without creating service/queue rows.
- `actionValidateOrder` remains active and `actionOrderStatusPostUpdate` now re-runs orchestration when payment is accepted later.
- Paid/accepted orders create local service and queue records only; hooks do not call provider APIs.
- Added `/module/ntresellerclub/myservices` for customer-owned domain and TR domain service visibility.
- Admin Domains and Admin TR Domains now show read-only latest 100 service/queue rows.
- Module version is now `0.1.1` with `upgrade/install-0.1.1.php` repairing hooks, tabs, schema, and configuration defaults.
- Task 06 simple admin UX remains the base and is preserved.

## Security

- No register, transfer, or renew provider API calls were added to hooks or frontend controllers.
- Provider API calls remain limited to explicit domain search availability checks and existing connection-test actions.
- Domain cart add rechecks availability server-side and does not trust client price or availability.
- Customer pages do not expose raw provider errors or credentials.
- Admin last-error output is sanitized for credential-like values.
- Upgrade/default repair does not overwrite existing API credentials or product mapping values.

## Performance

- Customer services reads only the current customer's domain/tr_domain rows and latest queue status.
- Admin domain visibility is capped to the latest 100 rows.
- Cart add performs one availability recheck and lightweight duplicate lookup.
- Dashboard/settings page loads remain provider-API free.

## Last Test

- Static file presence check covered recovered front controllers, templates, assets, metadata classes, orchestrator, upgrade script, and settings controller.
- Static scan verified no register/transfer/renew provider API call was added to hooks or frontend controllers.
- Static scan verified version `0.1.1`, `actionOrderStatusPostUpdate`, `displayHeader`, `ensureConfigurationDefaults`, `product_mapping_missing`, and `cart_metadata_invalid` are present.
- PHP lint could not be run because PHP CLI is not installed in this workspace.

## Known Risks

- Real PrestaShop 1.7/8/9 install and upgrade smoke tests still need to be run.
- Real provider availability tests require valid credentials and server network access.
- Cart pricing in the visible PrestaShop cart still depends on mapped product configuration; `price_snapshot` is stored for domain metadata/audit.
