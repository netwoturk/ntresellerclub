# Domain Search API Flow

Branch: `codex/task-02-domain-search-api-flow`

## Scope

`NtRcDomainSearchService` is the single read-only domain search flow for this task. It uses existing provider, route, and Engine 11 pricing classes.

No register, transfer, renew, customer, hosting, SSL, queue write, or dashboard provider call is added here.

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
      "error": null
    }
  ],
  "cached": false,
  "checked_at": "2026-06-28 12:00:00"
}
```

## Security

- Raw provider payloads are not returned.
- Credential-like values are removed from provider errors before JSON output.
- The endpoint does not log credentials.
- Dashboard opening remains provider-API free.

## Performance

- One availability call per explicit search request.
- One pricing row lookup per result.
- A 60-second runtime cache avoids repeated calls for the same normalized domain inside the process.
