# 07 - ResellerClub Architecture

## Kapsam

ResellerClub bu modülde global domain, hosting ve SSL servislerinin ana provider'ıdır.

## Kullanım Alanları

| Servis | Durum |
|---|---|
| Global domain | Aktif |
| Hosting | Aktif |
| SSL | Aktif |
| TR domain | Kullanılmaz |

## Customer API

Doğrulanan customer endpointleri:

- `customers/search.json`
- `customers/v2/signup.json`
- `customers/modify.json`
- `customers/details.json`

## Customer Akışı

```text
customer/create queue
  -> searchCustomer(email)
  -> varsa mapping active
  -> yoksa createCustomer(payload)
  -> provider_customer_id kaydet
```

## Domain Provisioning

Global domain işlemleri queue üzerinden yürür:

- register
- transfer
- renew
- details
- nameserver_update
- contact_update

## Hosting Provisioning

Hosting işlemleri sadece ResellerClub adapter üzerinden yürür:

- create
- renew
- suspend
- unsuspend
- details

## SSL Provisioning

SSL işlemleri sadece ResellerClub adapter üzerinden yürür:

- create
- renew
- details

## Güvenlik

`auth-userid`, `api-key`, `passwd`, `auth-code` loglanmaz ve response içinde saklanmaz.

## Kural

ResellerClub API parametreleri resmi dokümanla doğrulanmadan production akışına bağlanmaz.
