# Admin Framework Devbook

## Adding A New Admin Screen

1. Add the section to `NtRcAdminNavigationBuilder::tabs()`.
2. Create a thin `AdminNtRc...Controller` that extends `NtRcAdminBaseController`.
3. Set only `$ntRcSection`.
4. Put reusable UI in widgets/layout/helpers, not in the controller.
5. Put data reads behind a data provider implementing `NtRcAdminDataProviderInterface`.

## Rules

- Do not call provider APIs from admin page load.
- Do not write SQL in controllers.
- Do not duplicate navigation arrays.
- Escape text through `NtRcAdminThemeHelper::esc()` or PrestaShop helpers.
- Use PrestaShop admin token conventions for forms.
- Keep heavy work in queue/cron processors.

## Widget Use

Use `NtRcAdminWidget` for:

- KPI cards,
- tables,
- alerts,
- status badges,
- statistic tiles.

New widget types should be generic and reusable across Dashboard, Queue, Monitoring, Billing, Pricing, and Settings screens.

## Data Providers

Admin data providers may compose existing backend managers and monitoring classes. They should not introduce new business rules.

Allowed example:

```php
$provider = new NtRcAdminDashboardDataProvider();
$summary = $provider->getSummary();
```

Disallowed example:

```php
$client = new NtRcApiClient(...);
$client->domainAvailability(...);
```

Provider sandbox tests belong in testing workflows, not admin page rendering.

## V1 Dashboard Binding

The Dashboard screen is bound through `NtRcAdminDashboardDataProvider`.

It reads only local backend tables and existing monitoring classes:

- service counts,
- operation queue summary,
- provider health snapshots,
- runtime health snapshots,
- billing summary,
- notification queue summary,
- latest failed operations.

It must not call `NtRcApiClient`, provider adapters, sandbox checks, or cron processors during page load.
