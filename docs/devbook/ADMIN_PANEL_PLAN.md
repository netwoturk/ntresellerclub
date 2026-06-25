# Admin Panel Plan

Bu doküman ntresellerclub admin panelinin ürün ve UX planını tanımlar. Kod geliştirmeye geçmeden önce ekranlar burada netleştirilir.

## Admin Panel Ana Menü

Önerilen menü yapısı:

```text
NetwoTürk Domain & Hosting
├── Dashboard
├── Provider Ayarları
├── Domain Yönetimi
├── Hosting Yönetimi
├── SSL Yönetimi
├── Queue Yönetimi
├── Monitoring & Health
├── Notification & Mail
├── Renewal & Lifecycle
├── Fiyat & Kur Motoru
├── Müşteri / Contact Profilleri
├── Loglar
├── Lisans
└── Sistem Ayarları
```

## 1. Dashboard

Gösterilecek widget'lar:

- Aktif domain sayısı
- Aktif hosting sayısı
- Aktif SSL sayısı
- Pending queue
- Failed queue
- Provider health
- Cron son çalışma zamanı
- Bugünkü başarılı işlem
- Bugünkü hatalı işlem
- Yaklaşan yenilemeler
- Expired servisler

## 2. Provider Ayarları

- ResellerClub API ayarları
- DomainNameAPI ayarları
- Test mode / live mode
- Provider lisans durumu
- Provider health status

## 3. Domain Yönetimi

- Domain listesi
- Provider
- Expiry date
- Status
- Auto renew
- Nameserver
- Contact status
- Manual sync
- Renew queue oluşturma

## 4. Hosting Yönetimi

- Hosting ürün eşleştirme
- Hosting servisleri
- Status
- Expiry
- Suspend/unsuspend queue altyapısı

## 5. SSL Yönetimi

- SSL ürün eşleştirme
- SSL servisleri
- Expiry
- Validation status

## 6. Queue Yönetimi

- Pending
- Processing
- Done
- Failed
- Retry failed
- Cancel queue
- Queue detail
- Payload sanitize view

## 7. Monitoring & Health

- Provider status
- Runtime memory
- Queue metrics
- Failed metrics
- Response time
- Cron history

## 8. Notification & Mail

- Template listesi
- Notification queue
- Sent/failed logs
- Admin recipient settings
- Technical admin email

## 9. Renewal & Lifecycle

- Renewal due services
- Payment required
- Expired
- Suspended
- Notice history

## 10. Fiyat & Kur Motoru

- Manuel kur
- TR domain fiyatları
- Kar modeli
- Fiyat geçmişi

## Admin UI Kuralı

Admin butonları ağır API işlemi çalıştırmaz. Sadece queue kaydı oluşturur veya güvenli batch başlatır.

## Sonraki Plan

Önce backend engine'ler tamamlanacak. Ardından Admin Dashboard Basic geliştirilecek.
