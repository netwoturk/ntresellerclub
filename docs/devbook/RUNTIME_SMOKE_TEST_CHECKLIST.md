# Runtime Smoke Test Checklist

Branch: `codex/v1-recovery-task04-05-admin-ux`

## Install / Upgrade

- Install module from `ntresellerclub.zip`.
- Verify module version is `0.1.1`.
- On upgrade, verify existing API credentials remain unchanged.
- On upgrade, verify existing `NTRC_DOMAIN_PRODUCT_ID` and `NTRC_TR_DOMAIN_PRODUCT_ID` values remain unchanged.
- Verify hooks are registered: `actionValidateOrder`, `actionOrderStatusPostUpdate`, `displayCustomerAccount`, `displayHeader`, `displayBackOfficeHeader`.
- Verify admin tabs are present under NetwoTurk Hosting.
- Verify schema repair completes without destructive changes.

## Admin Settings

- Open Settings page.
- Verify no provider API call is made on page load.
- Verify API key/password inputs are masked and empty.
- Verify Global Domain Product ID and TR Domain Product ID status labels show `Ayarlı`, `Eksik`, or `Ürün Pasif`.
- Save settings with blank secret fields and verify stored secrets remain valid.

## Domain Search

- Open `/module/ntresellerclub/domainsearchpage`.
- Search a global domain.
- Search a TR domain.
- Verify only `domainsearch` endpoint is called for search.
- Verify available domains show `Sepete Ekle`.
- Verify unavailable domains cannot be added.

## Add To Cart

- Add an available global domain.
- Add an available TR domain.
- Verify `ntresellerclub_cart_domain` row is created.
- Verify duplicate domain add returns `duplicate`.
- Remove product mapping and verify `product_mapping_missing`.
- Verify no register, transfer, or renew provider call happens during cart add.

## Order Flow

- Place an unpaid order and verify no service or queue is created.
- Change order status to payment accepted and verify orchestrator creates service and queue.
- Place a paid order directly and verify `actionValidateOrder` creates service and queue.
- Verify invalid cart metadata records `cart_metadata_invalid`.
- Verify invalid cart metadata does not create service or queue.

## Customer Services

- Open `/module/ntresellerclub/myservices`.
- Verify customer sees only own domain and TR domain services.
- Verify provider technical errors are not shown.
- Verify queue status appears when a queue exists.

## Admin Visibility

- Open Admin Domains.
- Open Admin TR Domains.
- Verify latest 100 rows are listed.
- Verify credential-like text in last errors is masked.
- Verify no provider API call is made on page load.

## Compatibility

- Repeat smoke test on PrestaShop 1.7.
- Repeat smoke test on PrestaShop 8.
- Repeat smoke test on PrestaShop 9.
