# Admin Screen Map & Backend Data Plan

Bu doküman admin panel ekranlarını, backend data provider ihtiyacını ve ileride Codex'e verilecek kod görevlerini netleştirir.

## Amaç

Admin paneli geliştirmeye başlamadan önce her ekranın:

- Görevi
- Veri kaynağı
- Aksiyonları
- Güvenlik kuralı
- Queue bağlantısı
- V1 / V2 kapsamı

belirlenir.

## Genel Admin Controller Yapısı

Önerilen controller yapısı:

```text
controllers/admin/
├── AdminNtRcDashboardController.php
├── AdminNtRcDomainsController.php
├── AdminNtRcHostingController.php
├── AdminNtRcSslController.php
├── AdminNtRcQueueController.php
├── AdminNtRcMonitoringController.php
├── AdminNtRcNotificationsController.php
├── AdminNtRcRenewalsController.php
├── AdminNtRcPricingController.php
├── AdminNtRcCustomersController.php
├── AdminNtRcLogsController.php
├── AdminNtRcLicenseController.php
└── AdminNtRcSettingsController.php
```

## Backend Data Provider Yapısı

Admin ekranlarının doğrudan SQL karmaşasına girmemesi için data provider sınıfları kullanılacaktır.

```text
classes/admin/
├── NtRcAdminDashboardDataProvider.php
├── NtRcAdminDomainDataProvider.php
├── NtRcAdminQueueDataProvider.php
├── NtRcAdminMonitoringDataProvider.php
├── NtRcAdminNotificationDataProvider.php
├── NtRcAdminRenewalDataProvider.php
├── NtRcAdminPricingDataProvider.php
└── NtRcAdminLogDataProvider.php
```

## 1. Dashboard

Controller:

`AdminNtRcDashboardController`

Data Provider:

`NtRcAdminDashboardDataProvider`

Veri kaynakları:

- `ntresellerclub_service`
- `ntresellerclub_operation_queue`
- `ntresellerclub_provider_health`
- `ntresellerclub_runtime_health`
- `ntresellerclub_notification_queue`
- `ntresellerclub_log`

Widgetlar:

- KPI Kartları
- Provider Health
- Queue Health
- Runtime Health
- Renewal Summary
- Failed Operations
- Recent Activity

V1 kapsamı:

- Read-only dashboard
- Quick link butonları
- Ağır API çağrısı yok

## 2. Domain Yönetimi

Controller:

`AdminNtRcDomainsController`

Data Provider:

`NtRcAdminDomainDataProvider`

Veri kaynakları:

- `ntresellerclub_service`
- `ntresellerclub_cart_domain`
- `ntresellerclub_provider_customer`
- `ntresellerclub_contact_profile`
- `ntresellerclub_operation_queue`

Aksiyonlar:

- Domain detayına git
- Renew queue oluştur
- Transfer queue görüntüle
- Contact status görüntüle
- Nameserver update queue oluştur

Kural:

Domain API çağrısı doğrudan çalışmaz.

## 3. Hosting Yönetimi

Controller:

`AdminNtRcHostingController`

V1 kapsamı:

- Hosting servis listesi
- Provider mapping görüntüleme
- Renewal hazırlığı

V2 kapsamı:

- Paket eşleştirme
- Suspend/unsuspend queue

## 4. SSL Yönetimi

Controller:

`AdminNtRcSslController`

V1 kapsamı:

- SSL servis listesi
- Expiry ve status görüntüleme

V2 kapsamı:

- CSR/validation yönetimi

## 5. Queue Yönetimi

Controller:

`AdminNtRcQueueController`

Data Provider:

`NtRcAdminQueueDataProvider`

Veri kaynakları:

- `ntresellerclub_operation_queue`

Ekranlar:

- Pending
- Processing
- Done
- Failed

Aksiyonlar:

- retryFailed
- cancel
- detail
- cleanup done

Kural:

Retry sadece queue status değiştirir. Provider API doğrudan çağrılmaz.

## 6. Monitoring

Controller:

`AdminNtRcMonitoringController`

Data Provider:

`NtRcAdminMonitoringDataProvider`

Veri kaynakları:

- `ntresellerclub_provider_health`
- `ntresellerclub_runtime_health`
- `ntresellerclub_provider_statistics`

V1 kapsamı:

- Provider durumu
- Runtime durumu
- Queue istatistikleri
- Cron son çalışma zamanı

## 7. Notification

Controller:

`AdminNtRcNotificationsController`

Data Provider:

`NtRcAdminNotificationDataProvider`

Veri kaynakları:

- `ntresellerclub_notification_template`
- `ntresellerclub_notification_queue`
- `ntresellerclub_notification_log`

V1 kapsamı:

- Template listesi
- Queue listesi
- Sent/failed log

V2 kapsamı:

- Template editör

## 8. Renewal

Controller:

`AdminNtRcRenewalsController`

Data Provider:

`NtRcAdminRenewalDataProvider`

Veri kaynakları:

- `ntresellerclub_service`
- `ntresellerclub_notice`
- `ntresellerclub_notification_queue`

V1 kapsamı:

- Renewal due servisler
- Payment required
- Expired servisler
- Reminder geçmişi

## 9. Pricing

Controller:

`AdminNtRcPricingController`

Data Provider:

`NtRcAdminPricingDataProvider`

Veri kaynakları:

- `ntresellerclub_price`
- `ntresellerclub_price_history`
- `ntresellerclub_exchange_rate_history`

V1 kapsamı:

- Manuel kur
- TR fiyat paneli
- Kar modeli
- Fiyat geçmişi

## 10. Customers & Contact

Controller:

`AdminNtRcCustomersController`

Veri kaynakları:

- `ntresellerclub_provider_customer`
- `ntresellerclub_contact_profile`
- `customer`

V1 kapsamı:

- Provider mapping listesi
- Contact profile listesi
- contact_ready durumu

## 11. Logs

Controller:

`AdminNtRcLogsController`

Veri kaynakları:

- `ntresellerclub_log`
- `ntresellerclub_notification_log`
- `ntresellerclub_operation_queue`

Kural:

Loglarda credential gösterilmez.

## 12. License

Controller:

`AdminNtRcLicenseController`

V1 kapsamı:

- Lisans anahtarı
- Feature durumları
- Lisans bitiş tarihi

## 13. Settings

Controller:

`AdminNtRcSettingsController`

V1 kapsamı:

- Provider ayarları
- Cron token/url
- Runtime settings
- Technical admin email
- Genel ayarlar

## V1 Admin Panel Geliştirme Sırası

1. Data provider klasörü ve dashboard provider
2. Admin Dashboard controller
3. Dashboard template
4. Queue controller + failed retry
5. Monitoring controller
6. Notification controller
7. Domain listesi
8. Settings ekranı

## Kabul Kriterleri

- Admin dashboard API çağrısı yapmadan açılır.
- Veri sadece DB snapshotlarından okunur.
- Failed queue retry çalışır.
- Credential hiçbir ekranda açık görünmez.
- PrestaShop admin token ve güvenlik yapısı korunur.
- Çoklu dil altyapısı bozulmaz.
