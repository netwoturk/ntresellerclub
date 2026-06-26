# Current Status

Date: 2026-06-26

## Last Work

Engine 14 - SSL Provisioning Engine

## Last Branch

`codex/engine-14-ssl-provisioning`

## Completed

- SSL backend provisioning was added through `NtRcSslManager`.
- SSL product mapping is handled by `ntresellerclub_ssl_product_mapping`.
- SSL queue actions are `ssl/create`, `ssl/renew`, `ssl/reissue`, `ssl/cancel`, `ssl/details`, and `ssl/download`.
- SSL queue processing extends the existing hosting/domain/billing queue chain.
- ResellerClub is the only SSL provider; DomainNameAPI SSL is blocked by contract guard.
- ResellerClub SSL adapter returns controlled TODO/failure responses until official endpoints are verified.
- SSL renew blocks provider queue creation until payment confirmation and records billing/payment-required state.
- SSL lifecycle notifications include `ssl_created`, `ssl_renewed`, `ssl_expired`, `ssl_reissue_required`, and `payment_required`.
- Backend monitoring exposes SSL active, failed, pending, and expiring metrics.
- Installer, install SQL, and uninstall SQL include SSL product mapping schema.

## Database Changes

New table:

- `ntresellerclub_ssl_product_mapping`

Key fields:

- `id_product`
- `provider_code`
- `provider_product_id`
- `billing_cycle`
- `currency`
- `active`
- `created_at`
- `updated_at`

Updated table:

- `ntresellerclub_service.ssl_certificate_number`

## TODO

- Verify and implement real ResellerClub SSL endpoint/resource/action details before executing SSL provider calls.
- Connect provider-credit retry actions to the future Admin Operations UI.
- Connect renewal payment confirmation references to the future billing/invoice engine.

## Known Risks

- PHP runtime and real PrestaShop runtime tests were not available in this environment.
- SSL endpoint execution is intentionally TODO until official ResellerClub SSL API details are verified.
- Product classification depends on SSL product mappings or `SSL:` product references.

## Last Test

- New and changed PHP files passed a brace/parenthesis balance check.
- `php -l` could not be run because PHP CLI is not available in this workspace.
- SSL queue contract, DomainNameAPI blocking, payment-required renew path, and credential/CSR/certificate sanitization were statically reviewed.

## Last Documentation Update

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/architecture/17_SSL_PROVISIONING_ENGINE.md`
- `prestashop/ntresellerclub/docs/API_CONTRACT_RULES.md`
- `prestashop/ntresellerclub/docs/DATABASE_SCHEMA.md`
