# Current Status

Date: 2026-06-26

## Last Work

Engine 17 - PrestaShop Admin Framework

## Last Branch

`codex/engine-17-admin-framework`

## Completed

- Added shared PrestaShop admin framework for completed Foundation and Business Layer engines.
- Added main admin menu root: `NetwoTurk Hosting`.
- Added child admin sections: Dashboard, Domains, TR Domains, Hosting, SSL, Queue, Billing, Monitoring, Notifications, Pricing, BTK CSV, Logs, Settings, License.
- Added `NtRcAdminBaseController` with shared toolbar title, permission helper, token helper, flash helper, and render flow.
- Added shared layout, navigation builder, theme helper, widget helper, and dashboard data provider interface.
- Existing SSL admin controller was reused and moved onto the shared base controller.
- Installer now creates/removes admin tabs through a single navigation definition.
- Legacy admin API test action now performs local readiness checks and does not call provider APIs.

## Database Changes

No module table was added in Engine 17.

Admin menu records use PrestaShop's native `Tab` model during install/uninstall.

## Security

- Shared widget/theme helpers escape text with `Tools::safeOutput`.
- Admin pages keep PrestaShop token and permission conventions.
- CSRF helper is available in the base controller.
- Framework screens do not call ResellerClub or DomainNameAPI.

## Performance

- Dashboard reads existing backend summaries only.
- No provider API call is executed while opening admin framework pages.
- No heavy processing runs from admin page load.

## TODO

- Build full Dashboard UI on top of this framework.
- Bind each section to its dedicated data provider in future screen engines.
- Add richer PrestaShop permission profiles if role-specific operations are introduced.
- Run real PrestaShop 1.7, 8, and 9 install/upgrade smoke tests.

## Known Risks

- PHP CLI and real PrestaShop runtime tests were not available in this workspace.
- Admin tabs need module install/upgrade execution in a real PrestaShop back office to verify visual placement.
- Current non-dashboard section pages are intentional skeletons.

## Last Test

- Repository was scanned for existing controllers/helpers/renderers/data providers before implementation.
- Static check verified admin framework code does not call provider API clients.
- Static check verified dashboard provider uses existing backend summary classes.
- Static check verified `curl_exec` remains isolated in `NtRcApiClient`.
- Static check verified `Mail::Send` remains notification-queue based.
- `git diff --check` was run.
- PHP lint could not be run because PHP CLI is not available in this workspace.

## Last Documentation Update

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/admin-panel-architecture.md`
- `docs/architecture/20_ADMIN_FRAMEWORK.md`
- `docs/devbook/ADMIN_FRAMEWORK.md`
- `docs/database-schema.md`
- `prestashop/ntresellerclub/docs/DATABASE_SCHEMA.md`
- `prestashop/ntresellerclub/docs/ROADMAP.md`
