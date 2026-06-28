# Current Status

Date: 2026-06-28

## Last Work

V1 Admin Dashboard Data Binding

## Last Branch

`codex/v1-admin-dashboard-data-binding`

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

## Database Changes

No module table was added in this work.

The dashboard reads existing service, operation queue, provider health, runtime health, billing event, and notification queue tables.

## Security

- Shared widget/theme helpers escape text with `Tools::safeOutput`.
- Admin pages keep PrestaShop token and permission conventions.
- CSRF helper is available in the base controller.
- Framework screens do not call ResellerClub or DomainNameAPI.
- Dashboard output does not render payloads, responses, credentials, api keys, auth codes, tokens, passwords, private keys, CSR, or certificate raw values.
- `last_error` values shown on the dashboard are sanitized before rendering.

## Performance

- Dashboard reads existing backend summaries and lightweight aggregate queries only.
- No provider API call is executed while opening admin framework pages.
- No heavy processing runs from admin page load.
- Failed operations are capped at the latest 10 records.

## TODO

- Bind each section to its dedicated data provider in future screen engines.
- Add richer PrestaShop permission profiles if role-specific operations are introduced.
- Run real PrestaShop 1.7, 8, and 9 install/upgrade smoke tests.

## Known Risks

- PHP CLI and real PrestaShop runtime tests were not available in this workspace.
- Admin tabs need module install/upgrade execution in a real PrestaShop back office to verify visual placement.
- Current non-dashboard section pages are intentional skeletons.
- Dashboard data freshness depends on cron/monitoring snapshots for provider and runtime health.

## Last Test

- Repository was scanned for existing controllers/helpers/renderers/data providers before implementation.
- Static check verified admin dashboard code does not call provider API clients or queue processors.
- Static check verified dashboard provider does not render operation payload/response fields.
- Static check verified dashboard credential-like values are masked in displayed error text.
- Static check verified dashboard aggregate queries use existing local tables only.
- PHP lint could not be run because PHP CLI is not available in this workspace.

## Last Documentation Update

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/devbook/ADMIN_DASHBOARD_DETAILED_SPEC.md`
