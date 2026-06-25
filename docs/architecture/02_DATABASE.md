# 02 - Database Architecture

## Ana Tablolar

| Tablo | Görev |
|---|---|
| `ntresellerclub_provider` | Provider tanımları |
| `ntresellerclub_tld_route` | TLD -> provider yönlendirme |
| `ntresellerclub_provider_customer` | PrestaShop müşteri -> provider müşteri eşleşmesi |
| `ntresellerclub_contact_profile` | Kişisel/kurumsal contact profilleri |
| `ntresellerclub_cart_domain` | Sepetteki domain kayıtları |
| `ntresellerclub_service` | Domain/hosting/SSL servisleri |
| `ntresellerclub_operation_queue` | Ağır API işlem kuyruğu |
| `ntresellerclub_price` | TR domain maliyet/satış fiyatları |
| `ntresellerclub_price_history` | Fiyat geçmişi |
| `ntresellerclub_exchange_rate_history` | Kur geçmişi |
| `ntresellerclub_log` | Sistem logları |
| `ntresellerclub_license` | Lisans ve feature bilgileri |

## Temel İlişki

```text
PrestaShop Customer
  -> Contact Profile
  -> Provider Customer Mapping
  -> Service
  -> Operation Queue
```

## Provider Customer Ayrımı

Aynı PrestaShop müşterisinin ResellerClub ve DomainNameAPI tarafındaki temsil biçimi farklı olabilir.

| Provider | Kayıt Şekli |
|---|---|
| ResellerClub | Gerçek customer account ID |
| DomainNameAPI | TR domain contact hazırlığı / contact_ready |

## Queue Tablosu

`ntresellerclub_operation_queue` üretim güvenliği için merkezi tablodur.

Zorunlu alanlar:

- provider_code
- service_type
- action
- priority
- status
- retry_count
- max_retries
- lock_token
- payload_json
- response_json

## Kurulum Kuralı

Yeni tablolar `sql/*.sql` ile kurulmalı; mevcut kurulumlarda eksik kolonlar installer schema guard ile tamamlanmalıdır.
