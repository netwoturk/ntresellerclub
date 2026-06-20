# Veritabanı Şeması

Bu dosya NetwoTürk ResellerClub PrestaShop modülünün WHMCS benzeri servis yönetimi için kullandığı tabloları açıklar.

## `ps_ntresellerclub_customer`

PrestaShop müşterisi ile ResellerClub customer kaydını eşleştirir.

| Alan | Açıklama |
|---|---|
| `id_customer` | PrestaShop müşteri ID |
| `resellerclub_customer_id` | ResellerClub customer ID |
| `email` | Müşteri e-posta |
| `phone` | Telefon |
| `company` | Firma |
| `status` | pending / active / error |

## `ps_ntresellerclub_contact`

Domain sahibi/administrative/technical contact verilerini tutar.

| Alan | Açıklama |
|---|---|
| `id_customer` | PrestaShop müşteri ID |
| `provider_contact_id` | ResellerClub contact ID |
| `contact_type` | domain / admin / tech / billing |
| `firstname`, `lastname` | Contact kişi bilgisi |
| `company` | Firma |
| `email`, `phone`, `country` | Contact iletişim bilgileri |

## `ps_ntresellerclub_service`

Müşterinin satın aldığı domain, hosting, SSL ve e-posta servislerini takip eder.

| Alan | Açıklama |
|---|---|
| `service_type` | domain / hosting / ssl / email |
| `domain_name` | Alan adı |
| `provider_order_id` | ResellerClub order ID |
| `start_date` | Başlangıç tarihi |
| `expiry_date` | Bitiş tarihi |
| `status` | pending / active / expired / suspended / restore |
| `renew_price` | Yenileme ücreti |
| `transfer_price` | Transfer ücreti |
| `restore_price` | Kurtarma ücreti |

## `ps_ntresellerclub_cart_domain`

Domain arama modülünden gelen ve sepete bağlanan domain seçimini tutar.

| Alan | Açıklama |
|---|---|
| `id_cart` | PrestaShop cart ID |
| `domain_name` | Seçilen domain |
| `years` | Kaç yıllık kayıt |

## `ps_ntresellerclub_price`

ResellerClub ürün/fiyat senkronizasyon kayıtları.

| Alan | Açıklama |
|---|---|
| `product_type` | domain / hosting / ssl |
| `code` | tld veya ürün kodu |
| `years` | Yıl |
| `cost_price` | Alış fiyatı |
| `sale_price` | Satış fiyatı |
| `currency` | Para birimi |

## `ps_ntresellerclub_notice`

Yenileme bildirimlerinin tekrar gönderilmesini önler.

| Alan | Açıklama |
|---|---|
| `id_service` | Servis ID |
| `notice_type` | renewal / expire / restore |
| `days_before` | 30 / 15 / 7 / 3 / 1 |
| `sent_at` | Gönderim tarihi |

## `ps_ntresellerclub_log`

API, cron ve provisioning logları.

## `ps_ntresellerclub_license`

Yıllık SaaS lisans takibi.
