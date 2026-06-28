# Current Status

Date: 2026-06-28

## Last Work

Task 02 - Domain Search API Flow

## Last Branch

`codex/task-02-domain-search-api-flow`

## Completed

- Added shared PrestaShop admin framework for completed Foundation and Business Layer engines.
- Added main admin menu root: `NetwoTurk Hosting`.
- Added child admin sections: Dashboard, Domains, TR Domains, Hosting, SSL, Queue, Billing, Monitoring, Notifications, Pricing, BTK CSV, Logs, Settings, License.
- Added `NtRcAdminBaseController` with shared toolbar title, permission helper, token helper, flash helper, and render flow.
- Added shared layout, navigation builder, theme helper, widget helper, and dashboard data provider interface.
- Existing SSL admin controller was reused and moved onto the shared base controller.
- Installer now creates/removes admin tabs through a single navigation definition.
- Legacy admin API test action now performs local readiness checks and does not call provider APIs.
- Dashboard controller now renders real data produced by `NtRcAdminDashboardDataProvider`.
- Dashboard KPIs are bound to service, queue, billing, and notification summaries.
- Provider health cards read latest monitoring snapshots for ResellerClub and DomainNameAPI.
- Queue, runtime, service overview, failed operations, notification summary, and quick action links are rendered on the dashboard.
- Settings admin screen now manages ResellerClub and DomainNameAPI API settings.
- ResellerClub and DomainNameAPI connection test buttons call provider APIs only on explicit submit.
- Connection test results are stored in `ntresellerclub_provider_health` when available and rendered as success/failed with sanitized last error and checked time.
- Domain search now runs through `NtRcDomainSearchService`.
- Domain inputs are normalized by removing protocol, `www`, path/query, spaces, and trailing dots.
- IDN domains are converted with PHP intl when available; otherwise search fails safely before provider calls.
- TR routed domains use DomainNameAPI and global domains use ResellerClub route/default behavior.
- Domain search results return a standard JSON shape with domain, tld, provider_code, available, status, price, currency, final_sale_price, and sanitized error fields.
- DomainNameAPI TR sale price and ResellerClub global manual/mapping price are read from Engine 11 pricing rows.
- `NtRcDomainSearchEngine` now delegates to the service to keep one search flow.
- Added a read-only `domainsearch` front controller returning JSON.

## Database Changes

No module table was added in this work.

The dashboard and settings test status read existing service, operation queue, provider health, runtime health, billing event, and notification queue tables.

Domain search reads Engine 11 pricing rows and uses a short runtime cache only; no new table was added.

## Security

- Shared widget/theme helpers escape text with `Tools::safeOutput`.
- Admin pages keep PrestaShop token and permission conventions.
- CSRF helper is available in the base controller.
- Framework screens do not call ResellerClub or DomainNameAPI.
- Dashboard output does not render payloads, responses, credentials, api keys, auth codes, tokens, passwords, private keys, CSR, or certificate raw values.
- `last_error` values shown on the dashboard are sanitized before rendering.
- Settings screens do not render API key/password values; secret inputs are blank with masked placeholders.
- Empty or masked secret submit values keep the existing stored Configuration value.
- Connection test errors are sanitized before flash, render, or provider health storage.
- Domain search does not log or render provider credentials, raw provider payloads, API keys, passwords, tokens, or auth codes.
- Provider technical errors in search responses are sanitized before JSON output.

## Performance

- Dashboard reads existing backend summaries and lightweight aggregate queries only.
- No provider API call is executed while opening admin framework pages.
- No heavy processing runs from admin page load.
- Failed operations are capped at the latest 10 records.
- Provider API calls are limited to explicit connection test button submissions.
- Domain search provider calls run only from the explicit read-only search endpoint/service invocation.
- Search uses one availability call for the requested domain and a single pricing row lookup.
- Search responses are runtime-cached for 60 seconds per normalized domain inside the request process.

## TODO

- Build customer-facing search UI and add-to-cart flow on top of the read-only JSON endpoint.
- Bind Queue and Monitoring sections to dedicated data providers in the next admin screen task.
- Add richer PrestaShop permission profiles if role-specific operations are introduced.
- Run real PrestaShop 1.7, 8, and 9 install/upgrade smoke tests.

## Known Risks

- PHP CLI and real PrestaShop runtime tests were not available in this workspace.
- Admin tabs need module install/upgrade execution in a real PrestaShop back office to verify visual placement.
- Current non-dashboard section pages are intentional skeletons.
- Dashboard data freshness depends on cron/monitoring snapshots for provider and runtime health.
- Real connection tests require valid provider credentials and network access from the PrestaShop server.
- DomainNameAPI test depends on the bundled/installed DomainNameAPI SDK being present.
- Real search requires provider credentials, provider Configuration enabled state, and network access from the PrestaShop server.
- ResellerClub availability depends on the existing `NtRcApiClient::domainAvailability()` endpoint path already present in the module; official endpoint notes remain documented in `docs/resellerclub-api-analysis.md`.

## Last Test

- Repository was scanned for existing controllers/helpers/renderers/data providers before implementation.
- Static check verified admin dashboard code still does not call provider API clients or queue processors.
- Static check verified Settings provider API calls exist only in explicit connection test handlers.
- Static check verified dashboard provider does not render operation payload/response fields.
- Static check verified dashboard/settings credential-like values are masked in displayed error text.
- Static check verified dashboard aggregate queries use existing local tables only.
- Static check verified dashboard files still do not call provider availability or API clients.
- Static check verified domain search provider calls are isolated in `NtRcDomainSearchService`.
- Static check verified search JSON does not include raw provider response fields.
- PHP lint could not be run because PHP CLI is not available in this workspace.

## Last Documentation Update

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/devbook/ADMIN_DASHBOARD_DETAILED_SPEC.md`
- `docs/devbook/ADMIN_SCREEN_MAP.md`
- `docs/devbook/DOMAIN_SEARCH_API_FLOW.md`
