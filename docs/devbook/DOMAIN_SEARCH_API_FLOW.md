# Domain Search API Flow

Branch: `codex/v1-recovery-task04-05-admin-ux`

## Scope

`NtRcDomainSearchService` remains the single read-only domain search flow. The recovered customer UI calls the existing search endpoint and the existing cart endpoint only.

No register, transfer, renew, hosting, SSL, queue processor, or dashboard provider API call is added to the UI or hooks.

## Customer Page

Page:

`/module/ntresellerclub/domainsearchpage`

Files:

- `controllers/front/domainsearchpage.php`
- `views/templates/front/domain_search.tpl`
- `views/js/domain-search.js`
- `views/css/domain-search.css`

AJAX endpoints:

- Search: `/module/ntresellerclub/domainsearch`
- Add to cart: `/module/ntresellerclub/domaincart?action=add`

## Add To Cart

Available domains show `Sepete Ekle`. The button posts safe metadata to `domaincart?action=add` and shows friendly messages for:

- `product_mapping_missing`: `Ürün eşleştirmesi yapılmamış.`
- `unavailable`: domain is no longer available.
- `duplicate`: domain already exists in the cart.
- `failed`: generic safe failure.

Success shows `Domain sepete eklendi` and a `Sepete Git` link.

## Cart Endpoint

Accepted inputs:

- `domain`
- `years`
- optional `id_product`
- optional `cart_token`

Flow:

- Creates or reuses the current PrestaShop cart.
- Re-runs `NtRcDomainSearchService::search()` for availability.
- Rejects unavailable domains.
- Resolves product mapping from explicit `id_product`, `NTRC_DOMAIN_PRODUCT_ID`, or `NTRC_TR_DOMAIN_PRODUCT_ID`.
- Rejects inactive/missing product mappings with `product_mapping_missing`.
- Adds one mapped product to the cart.
- Inserts one `ntresellerclub_cart_domain` metadata row.
- Rejects duplicate domains in the same cart.

## Cart Metadata

`ntresellerclub_cart_domain` stores:

- `id_cart`
- `id_product`
- `domain_name`
- `tld`
- `provider_code`
- `service_type`
- `years`
- `price_snapshot`
- `currency`
- `options_json`
- `created_at`
- `updated_at`

## Order Handoff

`NtRcCartDomain::getDomainsByCart()` returns normalized rows for `NtRcOrderOrchestrator`.

`NtRcOrderOrchestrator` validates:

- `domain_name`
- `tld`
- `provider_code`
- `service_type`
- `years`
- `id_product`
- `price_snapshot`
- `currency`

Invalid metadata records `cart_metadata_invalid`, creates an admin notification, and skips service/queue creation.

## Paid Order Flow

- `actionValidateOrder` remains the direct paid-order entry point.
- `actionOrderStatusPostUpdate` re-runs orchestration when payment is accepted later.
- Paid/accepted states create local service and queue rows.
- Unpaid/cancelled/refunded/error/failed states do not create queue rows.
- Hooks do not call provider APIs.

## Customer Services

Page:

`/module/ntresellerclub/myservices`

The page lists only the logged-in customer's `domain` and `tr_domain` services with provider, service status, latest queue status, expiry date, and created time. Provider technical errors are not shown to customers.

## Security

- Raw provider payloads are not returned.
- Credential-like values are sanitized before JSON/admin rendering.
- Cart add does not trust client-side availability or price.
- Register, transfer, and renew are not executed by search, cart, or hooks.
