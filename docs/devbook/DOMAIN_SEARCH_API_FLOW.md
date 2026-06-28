# Domain Search API Flow

Branch: `codex/task-03-domain-search-cart-flow`

## Scope

`NtRcDomainSearchService` is the single read-only domain search flow for this task. It uses existing provider, route, and Engine 11 pricing classes.

No register, transfer, renew, customer, hosting, SSL, queue write, or dashboard provider call is added here. Task 03 adds cart metadata and product add flow only.

## Input Normalization

- Removes `http://`, `https://`, `www.`, path/query fragments, spaces, and trailing dots.
- Converts to lowercase.
- Converts IDN to punycode when PHP intl `idn_to_ascii()` is available.
- Fails safely before provider calls when IDN conversion is unavailable or invalid.

## Routing

- `.tr`, `.com.tr`, `.net.tr`, `.org.tr`, `.av.tr`, `.gen.tr`, and `.web.tr` route to DomainNameAPI.
- Other TLDs use `NtRcTldRouteManager`.
- If no non-TR route exists, the service falls back to ResellerClub for global domains.

## Provider Calls

- ResellerClub availability calls the existing `NtRcResellerClubProvider::checkAvailability()` path.
- DomainNameAPI TR availability calls the existing `NtRcDomainNameApiProvider::checkAvailability()` path.
- The ResellerClub domain availability endpoint is not redefined in this task; it uses the module's existing `NtRcApiClient::domainAvailability()` implementation. Keep endpoint verification notes in `docs/resellerclub-api-analysis.md` current before changing that client.

## Pricing

- DomainNameAPI TR register price reads Engine 11 rows: `provider_code=domainnameapi`, `product_type=tr_domain`, `code=<tld>:register`.
- ResellerClub global register price reads Engine 11 rows: `provider_code=resellerclub`, `product_type=domain`, `code=<tld>:register`.
- `NtRcPricingManager::calculateRow()` produces `final_sale_price`.
- Missing pricing returns `null` price fields and does not block availability.

## JSON Shape

```json
{
  "success": true,
  "query": "https://www.example.com",
  "normalized_domain": "example.com",
  "results": [
    {
      "domain": "example.com",
      "tld": "com",
      "provider_code": "resellerclub",
      "available": true,
      "status": "available",
      "price": 8.5,
      "currency": "TRY",
      "final_sale_price": 399.9,
      "error": null,
      "add_to_cart": {
        "domain": "example.com",
        "tld": "com",
        "provider_code": "resellerclub",
        "service_type": "domain",
        "years": 1,
        "final_sale_price": 399.9,
        "cart_token": "safe-hash"
      }
    }
  ],
  "cached": false,
  "checked_at": "2026-06-28 12:00:00"
}
```

## Cart Endpoint

Endpoint:

`/module/ntresellerclub/domaincart?action=add`

Accepted inputs:

- `domain`
- `years`
- optional `id_product`
- optional `cart_token`

Flow:

- Creates or reuses the current PrestaShop cart.
- Re-runs `NtRcDomainSearchService::search()` for availability.
- Rejects the add if the domain is no longer available.
- Resolves a domain product from explicit `id_product`, `NTRC_DOMAIN_PRODUCT_ID`, or `NTRC_TR_DOMAIN_PRODUCT_ID`.
- Adds one product quantity to the cart.
- Inserts one `ntresellerclub_cart_domain` row.
- Rejects duplicates for the same `id_cart` and `domain_name`.

Cart metadata fields:

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

Cart JSON response:

```json
{
  "success": true,
  "message": "Domain sepete eklendi.",
  "cart_id": 123,
  "id_product": 45,
  "domain": "example.com",
  "provider_code": "resellerclub",
  "service_type": "domain",
  "years": 1,
  "final_sale_price": 399.9,
  "currency": "TRY"
}
```

## Order Handoff

`NtRcOrderOrchestrator` already reads `NtRcCartDomain::getDomainsByCart($order->id_cart)` before processing product lines. The extended cart domain row carries `id_product`, `provider_code`, `service_type`, and price snapshot for downstream provisioning/audit. DomainNameAPI cart domains are preserved as `tr_domain` service records.

## Security

- Raw provider payloads are not returned.
- Credential-like values are removed from provider errors before JSON output.
- The endpoint does not log credentials.
- Dashboard opening remains provider-API free.
- Cart add does not trust client-side price or availability.
- Cart add does not execute register, transfer, or renew.

## Performance

- One availability call per explicit search request.
- One pricing row lookup per result.
- A 60-second runtime cache avoids repeated calls for the same normalized domain inside the process.
