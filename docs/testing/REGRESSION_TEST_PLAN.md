# Regression Test Plan

This plan protects Engines 11-16.

## Install And Upgrade

- Fresh install creates all module tables through installer SQL/schema guards.
- Upgrade from Engine 15 branch keeps existing rows.
- No manager class outside `NtRcInstaller` contains table creation SQL.
- Uninstall path removes module-owned tables according to existing uninstall policy.

## Order Orchestration

- Paid global domain order creates ResellerClub domain queue.
- Paid TR domain order creates DomainNameAPI TR domain queue.
- Paid hosting mapped product creates ResellerClub hosting service + queue.
- Paid SSL mapped product creates ResellerClub SSL service + queue.
- Duplicate provisioning attempt is skipped and recorded.
- Unpaid/cancelled/refunded order does not create provider API queue.

## Pricing

- Domain, TR domain, hosting, and SSL pricing use Engine 11 classes.
- SSL mapping write updates the existing pricing row.
- No new pricing engine/table is introduced.

## Billing

- Billing events are written through `NtRcBillingEventManager`.
- Renewal without payment records `renewal_payment_required`.
- Provider credit shortage records `provider_credit_required`.
- Billing event metadata is sanitized.

## Queue And Cron

- Cron processor handles domain, hosting, SSL, provider customer, and billing-aware failure paths.
- SSL processor dispatches only `resellerclub` provider.
- DomainNameAPI SSL and hosting actions fail at contract guard.
- Failed queues respect retry count and final failure status.

## Notification

- Lifecycle events enqueue notification rows.
- Mail sending occurs only from notification queue processing.
- Templates exist for:
  - `ssl_created`
  - `ssl_renewed`
  - `ssl_expired`
  - `ssl_reissue_required`
  - `payment_required`
  - `provider_credit_required`

## Monitoring

- Dashboard/statistics can read:
  - SSL active
  - SSL failed
  - SSL pending
  - SSL expiring
  - provider credit required
  - billing failures

## Security

- Logs and payload echoes must not contain:
  - api-key
  - password
  - credential
  - csr
  - private key
  - certificate raw
  - token
  - auth-code

## Compatibility

Run smoke tests on:

- PrestaShop 1.7
- PrestaShop 8
- PrestaShop 9

Minimum smoke flow:

1. Install module.
2. Save provider settings.
3. Save runtime settings.
4. Add hosting mapping.
5. Add SSL mapping.
6. Place paid mapped order.
7. Process cron.
8. Verify service, queue, billing, notification, and monitoring output.
