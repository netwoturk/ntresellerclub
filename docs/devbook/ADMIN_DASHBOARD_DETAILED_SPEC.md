# Admin Dashboard Detailed Specification

Bu doküman ntresellerclub admin dashboard ekranının detaylı planıdır. Kodlama yapılmadan önce dashboard widgetları, veri kaynakları ve aksiyon kuralları burada netleştirilir.

## Genel Kural

Admin dashboard ağır API işlemi çalıştırmaz. Dashboard sadece mevcut tablolar, monitoring snapshotları, queue ve servis kayıtlarından veri okur.

Aksiyon gerekiyorsa:

```text
Admin Button
  -> Queue Enqueue
  -> Cron
  -> Processor
```

## Dashboard Ana Yapısı

```text
NetwoTürk Domain & Hosting Dashboard
├── Üst KPI Kartları
├── Provider Health
├── Queue Health
├── Runtime Health
├── Service Overview
├── Renewal & Expiry
├── Failed Operations
├── Notification Status
├── Recent Activity
└── Quick Actions
```

## 1. Üst KPI Kartları

| Kart | Veri Kaynağı | Açıklama |
|---|---|---|
| Aktif Domain | ntresellerclub_service | service_type=domain, status=active |
| Aktif Hosting | ntresellerclub_service | service_type=hosting, status=active |
| Aktif SSL | ntresellerclub_service | service_type=ssl, status=active |
| Pending Queue | ntresellerclub_operation_queue | status=pending |
| Failed Queue | ntresellerclub_operation_queue | status=failed |
| Renewal Due | ntresellerclub_service | status=renewal_due |
| Expired | ntresellerclub_service | status=expired |
| Provider Warning | ntresellerclub_provider_health | status != OK |

## 2. Provider Health Paneli

Gösterilecek providerlar:

- ResellerClub
- DomainNameAPI

Alanlar:

- Provider adı
- Durum: OK / WARNING / DOWN / DISABLED
- Son kontrol zamanı
- Son başarılı işlem
- Son hata
- Ortalama response time
- Failed queue sayısı

Renkler:

| Durum | Renk |
|---|---|
| OK | Yeşil |
| WARNING | Sarı |
| DOWN | Kırmızı |
| DISABLED | Gri |

## 3. Queue Health Paneli

Alanlar:

- Pending
- Processing
- Done today
- Failed
- Retry count
- Ortalama bekleme süresi
- En eski pending kayıt

Aksiyonlar:

- Failed queue listesine git
- Retry all failed butonu ileride eklenecek
- Cleanup done queue ileride eklenecek

Kural:

Retry butonu doğrudan API çalıştırmaz. Sadece failed kaydı pending yapar.

## 4. Runtime Health Paneli

Alanlar:

- PHP version
- PrestaShop version
- Memory limit
- Current memory
- Peak memory
- Cron last run
- Batch limit
- Last runtime warning

Kaynak:

- ntresellerclub_runtime_health
- Configuration
- RuntimeGuard

## 5. Service Overview

Tablo:

| Servis Tipi | Active | Pending | Renewal Due | Payment Required | Expired | Error |
|---|---|---|---|---|---|---|
| Domain |  |  |  |  |  |  |
| Hosting |  |  |  |  |  |  |
| SSL |  |  |  |  |  |  |

## 6. Renewal & Expiry Paneli

Alanlar:

- 30 gün içinde bitecek servisler
- 15 gün içinde bitecek servisler
- 7 gün içinde bitecek servisler
- 1 gün içinde bitecek servisler
- Süresi geçmiş servisler

Aksiyon:

- Servis detayına git
- Renewal queue oluşturma altyapısı ileride eklenecek

## 7. Failed Operations Paneli

Gösterilecek alanlar:

- Queue ID
- Provider
- Service type
- Action
- Domain
- Retry count
- Last error
- Created at

Aksiyonlar:

- Retry
- Cancel
- Detail

Güvenlik:

Payload sanitize edilmiş gösterilir. Credential gösterilmez.

## 8. Notification Status Paneli

Alanlar:

- Pending notification
- Sent today
- Failed notification
- Admin alerts
- Last mail error

Kaynak:

- ntresellerclub_notification_queue
- ntresellerclub_notification_log

## 9. Recent Activity

Kaynaklar:

- ntresellerclub_log
- ntresellerclub_operation_queue
- ntresellerclub_notification_log
- ntresellerclub_service_history

Gösterilecek son aktiviteler:

- Domain registered
- Domain renew queued
- Notification sent
- Provider warning
- Queue failed
- Service expired

## 10. Quick Actions

Butonlar:

- Cron URL kopyala
- Provider health refresh queue oluştur
- TR fiyat sync queue oluştur
- Failed queue ekranına git
- Notification queue ekranına git
- Runtime ayarlarına git

Kural:

Quick action doğrudan ağır API çağrısı yapamaz.

## Admin Dashboard V1 Kapsam Dışı

V1 backend planında aşağıdakiler hemen yapılmayacak:

- Grafik kütüphanesi entegrasyonu
- Gerçek zamanlı websocket
- Canlı API ping butonu
- Toplu provider API test
- Gelişmiş filtreli raporlama

Bunlar Admin Dashboard Pro veya Reporting Engine aşamasına bırakılır.

## Geliştirme Sırası

1. Admin dashboard backend data provider
2. Admin dashboard controller
3. Basic Smarty/Twig template
4. KPI kartları
5. Queue listesi
6. Provider health listesi
7. Runtime health listesi
8. Failed queue retry action
9. Notification status widget
10. Service overview widget

## Kabul Kriterleri

- Dashboard açıldığında ağır API çağrısı yapmaz.
- Dashboard shared hostingde hızlı açılır.
- Credential göstermez.
- Failed queue ve provider health görünür.
- Admin aksiyonları queue mantığını bozmaz.
