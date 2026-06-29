# Admin Screen Map

Branch: `codex/task-06-simple-admin-ux-redesign-v1`

## Dashboard

Controller:

- `AdminNtRcDashboardController`

Data source:

- `NtRcAdminDashboardDataProvider`

Rules:

- Reads existing DB and monitoring snapshots only.
- Does not call ResellerClub or DomainNameAPI.
- Does not render credential, payload, or raw response data.

## Settings

Controller:

- `AdminNtRcSettingsController`

Purpose:

- Provide a simple setup-first admin experience for non-developer store managers.
- Manage provider API settings, domain product mappings, TR pricing, runtime limits, and cron URL from one guided screen.
- Run explicit provider connection tests.

Layout:

- Kurulum Durumu
- API Bağlantıları
- Domain Satış Ayarları
- Fiyat ve Kur
- Sistem
- Gelişmiş Ayarlar

## Kurulum Durumu

Shows:

- License key readiness.
- ResellerClub enabled state.
- DomainNameAPI enabled state.
- Global domain product mapping state.
- TR domain product mapping state.
- Cron URL readiness.
- Last cron run when available.

## ResellerClub Settings

Configuration keys:

- `NTRC_FEATURE_RESELLERCLUB`
- `NTRC_LIVE_MODE`
- `NTRC_RESELLER_ID`
- `NTRC_API_KEY`
- `NTRC_RC_RESELLER_ID`
- `NTRC_LANG_PREF`

Screen fields:

- Aktif/Pasif
- Test/Canlı mod
- Reseller ID
- Auth User ID
- API Key masked placeholder

Connection test:

- Uses existing `NtRcProviderFactory::make('resellerclub', false)`.
- Calls existing provider/client path with a read-only customer search.
- Writes a local provider health snapshot when possible.

## DomainNameAPI Settings

Configuration keys:

- `NTRC_FEATURE_DOMAINNAMEAPI`
- `NTRC_DNA_TEST_MODE`
- `NTRC_DNA_USERNAME`
- `NTRC_DNA_PASSWORD`

Screen fields:

- Aktif/Pasif
- Test/Canlı mod
- Kullanıcı adı
- Şifre / API credential masked placeholder

Connection test:

- Uses existing `NtRcProviderFactory::make('domainnameapi', false)`.
- Calls existing provider path for TR price retrieval as a read-only connection check.
- Writes a local provider health snapshot when possible.

## Connection Result

Rendered fields:

- provider
- success / failed
- sanitized last_error
- checked_at

Storage:

- `ntresellerclub_provider_health`

## Domain Sales Settings

Configuration keys:

- `NTRC_DOMAIN_PRODUCT_ID`
- `NTRC_TR_DOMAIN_PRODUCT_ID`

Rendered fields:

- Global Domain PrestaShop Product ID
- TR Domain PrestaShop Product ID
- Product status: Ayarlı / Eksik / Pasif
- Domain search page link
- Customer services page link

## Price And Currency

Data sources:

- `NtRcManualExchangeRate`
- `NtRcTrPriceManager`
- `NtRcTrPriceCalculator`

Rendered fields/actions:

- USD -> TRY manual rate.
- DomainNameAPI TR price row creation button.
- Simplified TR price table.
- Sale price, margin mode, percent margin, and fixed margin fields.

## System

Configuration keys:

- `NTRC_MEMORY_LIMIT`
- `NTRC_TIME_LIMIT`
- `NTRC_CRON_BATCH_LIMIT`
- `NTRC_CRON_TOKEN`

Rendered fields/actions:

- Memory limit.
- Time limit.
- Cron batch limit.
- Cron URL.
- Cron URL copy button.
- Ayarları kaydet button.

## Advanced Settings

- SSL mapping is linked as a secondary action so it does not interrupt the main setup flow.
- BTK CSV is shown as a premium feature when inactive.

## Domain Search JSON Endpoint

Front controller:

- `NtresellerclubDomainsearchModuleFrontController`

Service:

- `NtRcDomainSearchService`

Purpose:

- Accept explicit read-only domain search requests through `domain` or `q`.
- Normalize the submitted domain.
- Route global domains to ResellerClub and TR domains to DomainNameAPI.
- Return standard JSON result fields for frontend/admin consumers.

Provider calls:

- ResellerClub availability uses the existing provider/client `checkAvailability()` path.
- DomainNameAPI TR availability uses the existing SDK-backed provider `checkAvailability()` path.
- No register, transfer, renew, customer, hosting, SSL, or dashboard provider call is made by this endpoint.

## Security Rules

- API key and password inputs render empty with masked placeholders.
- Empty or masked submitted secret values preserve the existing Configuration value.
- Provider credentials must not be logged.
- Provider credentials must not be rendered in HTML values.
- Provider API calls must run only after explicit connection test submission.
- Domain search provider API calls must run only after explicit search submission.
- Dashboard opening must remain provider-API free.
- Settings page opening must remain provider-API free.
- Blank secret submissions must preserve existing stored Configuration values.
- Credential-like values must be sanitized before rendering connection test errors.

## Compatibility

The screen uses:

- `ModuleAdminController`
- existing admin framework render helpers
- `Configuration`
- `Tools::safeOutput`
- `Context::link->getAdminLink`
- existing provider/client classes
