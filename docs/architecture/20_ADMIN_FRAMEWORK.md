# Engine 17 - PrestaShop Admin Framework

Branch: `codex/engine-17-admin-framework`

## Goal

Engine 17 creates the shared PrestaShop admin framework for the completed foundation and business layer. It does not create new provider integrations and does not call provider APIs.

## Admin Menu

Root menu:

- `NetwoTurk Hosting`

Child menus:

- Dashboard
- Domains
- TR Domains
- Hosting
- SSL
- Queue
- Billing
- Monitoring
- Notifications
- Pricing
- BTK CSV
- Logs
- Settings
- License

The menu is defined once in `NtRcAdminNavigationBuilder::tabs()` and installed through `NtRcInstaller::installAdminTabs()`.

## Controllers

Shared base:

- `NtRcAdminBaseController`

Thin section controllers:

- `AdminNtRcRootController`
- `AdminNtRcDashboardController`
- `AdminNtRcDomainsController`
- `AdminNtRcTrDomainsController`
- `AdminNtRcHostingController`
- `AdminNtRcSslController`
- `AdminNtRcQueueController`
- `AdminNtRcBillingController`
- `AdminNtRcMonitoringController`
- `AdminNtRcNotificationsController`
- `AdminNtRcPricingController`
- `AdminNtRcBtkCsvController`
- `AdminNtRcLogsController`
- `AdminNtRcSettingsController`
- `AdminNtRcLicenseController`

Each section controller only selects a section key. Shared rendering, permission helpers, token helpers, toolbar title, and flash messages live in the base controller.

## Layout

`NtRcAdminLayout` renders the common shell:

- header,
- breadcrumb,
- sidebar,
- content,
- footer.

The layout uses internal HTML helpers and escaped values. No provider data is fetched by the layout.

## Widgets

`NtRcAdminWidget` provides:

- KPI card,
- table,
- alert,
- status badge,
- statistic tile.

Widget text is escaped through `NtRcAdminThemeHelper::esc()`.

## Theme

`NtRcAdminThemeHelper` and `views/css/admin-framework.css` provide Bootstrap-compatible admin styling with light/dark compatibility. The stylesheet is loaded only for `AdminNtRc*` controllers through `hookDisplayBackOfficeHeader`.

## Data Provider Layer

`NtRcAdminDataProviderInterface` is the contract for admin screens.

`NtRcAdminDashboardDataProvider` is the Dashboard data source. V1 dashboard binding reads local snapshots and aggregate counters from existing module tables:

- operation queue,
- service,
- provider health,
- runtime health,
- notification queue.

It does not call provider APIs, run cron processors, or invoke installer schema guards during page load.

## Security

- Admin tabs use PrestaShop `Tab`.
- Controllers extend `ModuleAdminController`.
- Permission checks use PrestaShop controller access where available.
- Token and CSRF helper methods are centralized in `NtRcAdminBaseController`.
- Text output is escaped before rendering.
- Framework pages do not call ResellerClub or DomainNameAPI.

## Performance

- Dashboard opening reads only local backend summary data.
- No heavy queue processing runs during admin page load.
- Provider health checks remain cron/monitoring responsibilities.

## Future Work

- Bind real Dashboard widgets in a dedicated dashboard engine.
- Build Queue and Monitoring operation screens.
- Expand Settings and License screens with dedicated forms.
