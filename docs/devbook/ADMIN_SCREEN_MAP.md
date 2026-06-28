# Admin Screen Map

Branch: `codex/task-01-api-settings-connection-test`

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

- Manage provider API settings.
- Run explicit provider connection tests.

## ResellerClub Settings

Configuration keys:

- `NTRC_FEATURE_RESELLERCLUB`
- `NTRC_LIVE_MODE`
- `NTRC_RESELLER_ID`
- `NTRC_API_KEY`
- `NTRC_RC_RESELLER_ID`
- `NTRC_LANG_PREF`

Screen fields:

- enabled
- mode: sandbox/live
- auth_userid
- api_key
- reseller_id if available
- language

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

- enabled
- mode: sandbox/live
- username
- password / API credential

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

## Security Rules

- API key and password inputs render empty with masked placeholders.
- Empty or masked submitted secret values preserve the existing Configuration value.
- Provider credentials must not be logged.
- Provider credentials must not be rendered in HTML values.
- Provider API calls must run only after explicit connection test submission.
- Dashboard opening must remain provider-API free.

## Compatibility

The screen uses:

- `ModuleAdminController`
- existing admin framework render helpers
- `Configuration`
- `Tools::safeOutput`
- `Context::link->getAdminLink`
- existing provider/client classes
