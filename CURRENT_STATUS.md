# Current Status

Date: 2026-06-26

## Last Work

Engine 15 - SSL Endpoint Verification + Admin Mapping Backend

## Last Branch

`codex/engine-15-ssl-endpoint-verification-admin-mapping`

## Completed

- ResellerClub SSL endpoint verification was documented in `docs/resellerclub-api-analysis.md`.
- `NtRcResellerClubSslAdapter` now calls verified SSL add, details, reissue, delete/cancel, certificate details, and validation status paths.
- `renewSsl()` remains a controlled TODO because the current ResellerClub renew parameter contract was not fully verified.
- `ssl/validation_status` was added to queue contract and processor dispatch.
- DomainNameAPI remains blocked from every SSL queue action.
- SSL mapping backend was extended with `ssl_product_type`, `cost_price`, and `sale_price`.
- SSL mapping save/toggle/list backend skeleton was added.
- SSL mapping writes sync cost/sale data into the existing Engine 11 pricing manager.
- Provider credit failures now enqueue the `provider_credit_required` admin notification template.
- SSL monitoring exposes active, pending queue, failed queue, expiring, and provider-credit-required metrics.

## Database Changes

Updated table:

- `ntresellerclub_ssl_product_mapping`

Added/guarded fields:

- `ssl_product_type`
- `cost_price`
- `sale_price`

Existing Engine 14 fields remain:

- `id_product`
- `provider_code`
- `provider_product_id`
- `billing_cycle`
- `currency`
- `active`
- `created_at`
- `updated_at`

## Verified SSL Endpoints

- `/api/sslcert/add.json`
- `/api/sslcert/enroll.json`
- `/api/sslcert/reissue.json`
- `/api/sslcert/details.json`
- `/api/sslcert/delete.json`
- `/api/sslcert/change-verification-method.json`
- `/api/sslcert/validate-csr.json`
- `/api/sslcert/get-cert-details.json`

## TODO

- Verify current ResellerClub SSL renew endpoint parameter contract before enabling `renewSsl()`.
- Design secure transient/encrypted CSR handling before exposing automated customer reissue/enroll flows.
- Register a real PrestaShop admin tab for `AdminNtRcSslController` in a future UI engine if needed.
- Expand admin UI from skeleton to full UX in a later engine.

## Known Risks

- PHP runtime and real PrestaShop runtime tests were not available in this environment.
- `createSsl()` requires a ResellerClub `customer-id`; orders without provider customer mapping first enqueue/reuse the customer mapping queue and SSL provisioning waits for a later retry.
- `downloadSsl()` sanitizes certificate-like response fields, so raw certificate delivery needs a secure download design later.

## Last Test

- `git diff --check` was run.
- New and changed PHP files passed a brace/parenthesis balance check.
- `php -l` could not be run because PHP CLI is not available in this workspace.
- Static checks verified SSL queue contract, DomainNameAPI blocking, TODO renew behavior, and sensitive-field sanitization.

## Last Documentation Update

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/resellerclub-api-analysis.md`
- `docs/architecture/18_SSL_ENDPOINT_VERIFICATION_ADMIN_MAPPING.md`
- `prestashop/ntresellerclub/docs/API_CONTRACT_RULES.md`
- `prestashop/ntresellerclub/docs/DATABASE_SCHEMA.md`
- `prestashop/ntresellerclub/docs/ROADMAP.md`
