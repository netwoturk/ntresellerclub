# Engine 11 - Pricing & Currency Engine Finalization

## Purpose

Engine 11 finalizes the backend pricing, currency, margin, tax, and rounding engine used by domain, hosting, and SSL sales.

No provider pricing endpoint is invented in this engine. DomainNameAPI TR domain cost sync continues through the existing cron-backed `NtRcDomainNameApiPriceSync`. ResellerClub global domain, hosting, and SSL pricing is prepared as mapping infrastructure in `ntresellerclub_price` without adding speculative API calls.

## Core Classes

- `NtRcPricingEngine`: central price calculation engine.
- `NtRcPricingManager`: generic price mapping manager for `domain`, `tr_domain`, `hosting`, and `ssl` rows.
- `NtRcTrPriceCalculator`: backward-compatible wrapper around `NtRcPricingEngine`.
- `NtRcTrPriceManager`: DomainNameAPI TR manager backed by `NtRcPricingManager`.
- `NtRcManualExchangeRate`: manual exchange rate registry with USD target support.
- `NtRcDomainNameApiPriceSync`: cron-safe DomainNameAPI TR cost sync.

## Supported Currencies

Manual exchange rates support:

- USD
- TRY
- EUR
- GBP
- AZN

Required USD targets:

- USD -> TRY
- USD -> EUR
- USD -> GBP
- USD -> AZN

Rates are stored in PrestaShop configuration with the `NTRC_MANUAL_RATE_` prefix. Changes are written to `ntresellerclub_exchange_rate_history`.

## Margin Models

The engine supports:

- `manual`
- `percent`
- `fixed`
- `hybrid`

Manual mode interprets `sale_price` according to the row's `tax_included` flag. Percent, fixed, and hybrid modes calculate the sale price from converted cost before tax.

## Tax Model

The current backend supports:

- tax included rows
- tax excluded rows
- default tax rate through `NTRC_DEFAULT_TAX_RATE`
- currency-specific future override through `NTRC_TAX_RATE_{CURRENCY}`

The default fallback tax rate is 20%.

## Rounding Modes

Supported rounding modes:

- `no_round`
- `nearest_1`
- `nearest_5`
- `nearest_10`
- `psychological_99`

Rounding is applied to the visible final sale price after tax.

## Standard Calculation Result

Every successful calculation returns at least:

```php
array(
    'cost_price' => 10.00,
    'cost_currency' => 'USD',
    'converted_cost' => 400.00,
    'target_currency' => 'TRY',
    'margin_amount' => 80.00,
    'tax_amount' => 96.00,
    'sale_price_without_tax' => 480.00,
    'sale_price_with_tax' => 576.00,
    'rounding_mode' => 'psychological_99',
    'final_sale_price' => 576.99,
)
```

Backward-compatible keys such as `source_currency`, `cost_converted`, `sale_price`, and `mode` are also returned for existing renderers.

## Price Table Usage

`ntresellerclub_price` is the central table for:

- DomainNameAPI TR domains: `provider_code=domainnameapi`, `product_type=tr_domain`, code like `com.tr:register`.
- ResellerClub global domains: `provider_code=resellerclub`, `product_type=domain`, code like `com:register`.
- ResellerClub hosting: `provider_code=resellerclub`, `product_type=hosting`, code like `hosting:default:create`.
- ResellerClub SSL: `provider_code=resellerclub`, `product_type=ssl`, code like `ssl:default:create`.

Engine 11 seeds ResellerClub mapping placeholders with zero costs. Real costs can be inserted later by a verified provider pricing flow or admin backend logic.

## History

Price cost/sale changes continue to write to `ntresellerclub_price_history` through `NtRcHistoryManager`.

Exchange-rate changes write to `ntresellerclub_exchange_rate_history`.

## Cron / Heavy Work

DomainNameAPI price fetching remains cron guarded:

```text
Cron -> NtRcDomainNameApiPriceSync -> Provider getTrPrices -> NtRcTrPriceManager -> NtRcPricingManager
```

`NtRcDomainNameApiPriceSync` still uses `NtRcRuntimeGuard::beforeHeavyProcess('dna_price_sync')`.

## API Boundaries

- No direct pricing API call is made from controllers.
- No ResellerClub price endpoint is added in this engine.
- DomainNameAPI TR price fetching continues through the existing provider adapter method only.
- Raw credentials, API keys, passwords, auth codes, and tokens must not be written to price history or logs.
