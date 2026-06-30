# Runtime Smoke Test Checklist

Branch: `codex/task-09-v1-install-runtime-fix`

## Package

- ZIP root is `ntresellerclub/`.
- ZIP contains `ntresellerclub/ntresellerclub.php`.
- ZIP entries use forward slash paths only.

## Install / Upgrade

- Install module from `ntresellerclub.zip`.
- Verify module version is `0.1.1`.
- Verify `install()` completes without PHP fatal error.
- Verify `uninstall()` completes without PHP fatal error.
- Verify `upgrade/install-0.1.1.php` completes without PHP fatal error.
- Verify no SQLSTATE appears during install/upgrade.
- Verify existing API credentials and product mappings are not overwritten during upgrade.

## Hooks

- Verify registered hooks:
  - `actionValidateOrder`
  - `actionOrderStatusPostUpdate`
  - `displayCustomerAccount`
  - `displayHeader`
  - `displayBackOfficeHeader`

## Admin Screens

- Open Dashboard.
- Open Settings.
- Open Domains.
- Open TR Domains.
- Confirm no provider API call happens on admin page load.
- Confirm credential-like values are not rendered.

## Front Screens

- Open `/module/ntresellerclub/domainsearchpage`.
- Open `/module/ntresellerclub/domainsearch`.
- Open `/module/ntresellerclub/domaincart?action=add`.
- Open `/module/ntresellerclub/myservices`.
- Confirm CSS/JS assets load on the domain search page.
- Confirm provider technical errors are not shown to customers.

## SQL Checks

- Confirm no `SHOW COLUMNS ... LIMIT 1` query is used.
- Confirm no `TEXT DEFAULT NULL`, `MEDIUMTEXT DEFAULT NULL`, or `LONGTEXT DEFAULT NULL` remains.
- Confirm schema repair is non-destructive.

## Compatibility

- Repeat on PrestaShop 8 first.
- Repeat on PrestaShop 1.7.
- Repeat on PrestaShop 9.
