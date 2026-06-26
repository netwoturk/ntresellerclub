# NetwoTurk ntresellerclub Roadmap

Bu dosya ResellerClub + DomainNameAPI destekli PrestaShop domain/hosting/SSL modulunun gelistirme yol haritasidir.

## Temel Urun Kurali

- Global domainler ResellerClub uzerinden yonetilir.
- Hosting servisleri ResellerClub uzerinden yonetilir.
- SSL servisleri ResellerClub uzerinden yonetilir.
- TR uzantili domainler DomainNameAPI uzerinden yonetilir.
- DomainNameAPI bu modulde global domain, hosting veya SSL icin kullanilmaz.
- Agir islemler dogrudan API cagrisi yapmaz; queue + cron uzerinden calisir.

## Tamamlanan Engine Zinciri

| Engine | Durum |
|---|---|
| Core / Provider / Queue / Runtime | Baseline tamamlandi |
| Domain Provisioning | Tamamlandi |
| Monitoring & Health | Tamamlandi |
| Notification & Mail | Tamamlandi |
| Renewal / Lifecycle Notification | Tamamlandi |
| Pricing & Currency | Tamamlandi |
| BTK CSV Reporting | Tamamlandi |
| Hosting Provisioning | Backend engine tamamlandi |
| Billing / Order Orchestrator | Tamamlandi |
| SSL Provisioning | Backend engine tamamlandi |

## PHASE 08 - Hosting Provisioning

Durum: Backend engine tamamlandi (`codex/engine-12-hosting-provisioning`)

### Hedefler

- ResellerClub hosting urun eslestirme.
- PrestaShop urun -> provider product mapping.
- Hosting create queue.
- Hosting renew queue.
- Hosting suspend/unsuspend queue.
- Hosting lifecycle status altyapisi.
- Monitoring metrikleri.
- Hosting servis ekrani sonraki customer/admin UI engine kapsaminda kalir.

### Kabul Kriterleri

- Hosting sadece ResellerClub adapter uzerinden calisir.
- Hosting olusturma siparis aninda degil queue uzerinden olur.
- DomainNameAPI hosting tarafinda contract guard tarafindan reddedilir.
- Renew odeme alinmadan provider API queue olusturmaz; `payment_required` notification altyapisina baglanir.
- ResellerClub hosting endpointleri dogrulanmadigi surece adapter gercek API cagrisi yapmaz ve TODO cevabi dondurur.
- Admin UI eklenmez.

## PHASE 09 - SSL Provisioning

Durum: Backend engine tamamlandi (`codex/engine-14-ssl-provisioning`)

### Hedefler

- ResellerClub SSL urun eslestirme.
- SSL create/renew/reissue/cancel/details/download queue actionlari.
- SSL lifecycle status altyapisi.
- SSL notification queue entegrasyonu.
- SSL monitoring metrikleri.
- Billing ve pricing icin mevcut Engine 13 / Engine 11 altyapisini kullanma.

### Kabul Kriterleri

- SSL sadece ResellerClub adapter uzerinden calisir.
- DomainNameAPI SSL tarafinda contract guard tarafindan reddedilir.
- SSL islemleri queue + cron uzerinden calisir.
- Renew odeme alinmadan provider API queue olusturmaz.
- ResellerClub SSL endpointleri dogrulanmadigi surece adapter gercek API cagrisi yapmaz ve TODO cevabi dondurur.
- Admin UI eklenmez.

## Siradaki Fazlar

| Phase | Baslik | Not |
|---|---|---|
| 11 | Customer Panel | Hosting/domain/SSL servis gorunurlugu |
| 12 | Admin Panel | Queue, monitoring ve servis operasyon UI |
| 13 | Security / Production Hardening | Credential, logging, runtime ve edge case sertlestirme |
