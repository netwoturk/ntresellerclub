# Current Status

Date: 2026-06-28

## Last Work

Task 01 - API Settings & Connection Test

## Last Branch

`codex/task-01-api-settings-connection-test`

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

## Database Changes

No module table was added in this work.

The dashboard and settings test status read existing service, operation queue, provider health, runtime health, billing event, and notification queue tables.

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

## Performance

- Dashboard reads existing backend summaries and lightweight aggregate queries only.
- No provider API call is executed while opening admin framework pages.
- No heavy processing runs from admin page load.
- Failed operations are capped at the latest 10 records.
- Provider API calls are limited to explicit connection test button submissions.

## TODO

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

## Last Test

- Repository was scanned for existing controllers/helpers/renderers/data providers before implementation.
- Static check verified admin dashboard code still does not call provider API clients or queue processors.
- Static check verified Settings provider API calls exist only in explicit connection test handlers.
- Static check verified dashboard provider does not render operation payload/response fields.
- Static check verified dashboard/settings credential-like values are masked in displayed error text.
- Static check verified dashboard aggregate queries use existing local tables only.
- PHP lint could not be run because PHP CLI is not available in this workspace.

## Last Documentation Update

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/devbook/ADMIN_DASHBOARD_DETAILED_SPEC.md`
- `docs/devbook/ADMIN_SCREEN_MAP.md`
