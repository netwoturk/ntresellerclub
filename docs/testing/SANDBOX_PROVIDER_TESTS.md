# Sandbox Provider Tests

Engine 16 defines sandbox tests without hardcoding unverified provider endpoints.

## Scope

Provider sandbox tests cover:

- ResellerClub domain queue actions.
- ResellerClub hosting queue actions with controlled TODO adapter responses where endpoints are not verified.
- ResellerClub SSL verified actions from Engine 15.
- DomainNameAPI TR-domain-only routing.
- DomainNameAPI SSL/hosting blocking.

## ResellerClub SSL Tests

Required sandbox cases:

| Case | Expected Result |
|---|---|
| `ssl/create` with domain, months, customer-id, plan-id, invoice-option | Queue dispatches to ResellerClub SSL adapter |
| `ssl/create` missing customer-id | Controlled failure, no sensitive payload leak |
| `ssl/reissue` missing CSR | Controlled failure with missing field list |
| `ssl/reissue` with CSR | Adapter sanitizes CSR from payload/error output |
| `ssl/details` with order-id | Adapter uses verified details action |
| `ssl/download` with order-id | Adapter sanitizes certificate-like response fields |
| `ssl/validation_status` with order-id | Adapter uses verified details action |
| `ssl/renew` | Controlled TODO failure until endpoint contract is verified |

## DomainNameAPI Tests

Required sandbox cases:

| Case | Expected Result |
|---|---|
| `.com.tr` check/register action | Contract guard allows TR domain service |
| `.com` check/register action through DomainNameAPI | Contract guard blocks |
| `ssl/create` through DomainNameAPI | Contract guard blocks |
| `hosting/create` through DomainNameAPI | Contract guard blocks |

## Queue Tests

Required checks:

- A paid order creates service + operation queue records.
- Unpaid renew requests set `payment_required` and do not enqueue provider API calls.
- Provider credit failures become `provider_credit_required`.
- Retry flow can pick up failed/provider-credit queue items after admin action or balance top-up.
- Cron batch obeys configured shared-hosting limits.

## Evidence To Capture

For each sandbox run, capture:

- queue id,
- service id,
- action,
- provider code,
- sanitized provider response,
- service status after processing,
- notification queue row if expected,
- billing event row if expected.

Do not capture or store raw CSR, private key, certificate body, auth code, token, password, or API key.
