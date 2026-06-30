# Admin Screen Map

Branch: `codex/v1-recovery-task04-05-admin-ux`

## Settings

Controller:

- `AdminNtRcSettingsController`

Task 06 simple admin UX is preserved. The screen remains organized around:

- Kurulum Durumu
- API Bağlantıları
- Domain Satış Ayarları
- Fiyat ve Kur
- Sistem
- Gelişmiş Ayarlar

Product mapping readiness uses:

- `NTRC_DOMAIN_PRODUCT_ID`
- `NTRC_TR_DOMAIN_PRODUCT_ID`

Status labels:

- `Ayarlı`
- `Eksik`
- `Ürün Pasif`

Settings page load must not call provider APIs. Provider API calls remain limited to explicit connection test buttons.

## Domain Search Page

Front controller:

- `NtresellerclubDomainsearchpageModuleFrontController`

Template/assets:

- `views/templates/front/domain_search.tpl`
- `views/js/domain-search.js`
- `views/css/domain-search.css`

Rules:

- Calls the read-only `domainsearch` endpoint.
- Calls `domaincart?action=add` only for available domains.
- Shows friendly cart errors for product mapping, unavailable, duplicate, and generic failed cases.

## Customer Services Page

Front controllers:

- `NtresellerclubMyservicesModuleFrontController`
- `NtresellerclubServicesModuleFrontController`

Rules:

- Lists only the logged-in customer's `domain` and `tr_domain` services.
- Shows provider, service status, latest queue status, expiry date, and created date.
- Does not render provider technical error text to customers.

## Admin Domains

Controllers:

- `AdminNtRcDomainsController`
- `AdminNtRcTrDomainsController`

Rules:

- Read-only latest 100 service rows.
- Shows service status, latest queue status, queue action, expiry date, and sanitized last error.
- No provider API call is made.

## Runtime Upgrade

Upgrade script:

- `upgrade/install-0.1.1.php`

Repairs:

- hooks
- tabs
- schema
- configuration defaults

Existing API credentials and product mappings must not be overwritten.

## Security Rules

- API key and password inputs render empty with masked placeholders.
- Empty secret submissions preserve existing stored values.
- Provider credentials must not be logged or rendered.
- Dashboard and settings page opening must remain provider-API free.
- Domain search provider API calls must run only after explicit search submission.
- Domain cart add must not call register, transfer, or renew.
- Order hooks must not call provider APIs directly.
- Credential-like values must be sanitized before rendering errors.
