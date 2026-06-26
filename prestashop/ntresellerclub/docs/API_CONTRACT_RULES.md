# NetwoTurk ResellerClub / DomainNameAPI API Contract Rules

Bu dosya modul mimarisinde uyulacak zorunlu API sinirlarini tanimlar.

## Temel Mimari Kurali

Modul hicbir provider icin rastgele endpoint, varsayimsal parametre veya dokumanda olmayan islem kullanmayacaktir.

Tum provider cagrilari su katmanlardan gecmelidir:

1. Front / Admin Controller
2. Service / Manager sinifi
3. Queue Manager
4. Provider Adapter
5. Resmi API Client

Controller icinden dogrudan ResellerClub veya DomainNameAPI cagrisi yapilmayacaktir.

## Provider Ayrimi

| Servis | Provider | Kural |
|---|---|---|
| Global domain | ResellerClub | .com, .net, .org vb. global uzantilar |
| Hosting | ResellerClub | Hosting islemleri sadece ResellerClub |
| SSL | ResellerClub | SSL islemleri sadece ResellerClub |
| TR domain | DomainNameAPI | .tr, .com.tr, .net.tr, .org.tr vb. |

DomainNameAPI uzerinden hosting, SSL veya global domain satisi bu modulde aktif edilmeyecektir.

## Queue Kurali

Asagidaki islemler dogrudan calistirilmayacaktir:

| Islem | Direkt API cagrisi |
|---|---|
| Domain register | Yasak |
| Domain transfer | Yasak |
| Domain renew | Yasak |
| Hosting create | Yasak |
| Hosting renew | Yasak |
| Hosting suspend/unsuspend | Yasak |
| SSL create | Yasak |
| SSL renew/reissue/cancel/details/download/validation status | Yasak |
| Provider customer create | Yasak |
| Mail gonderimi | Yasak |

Bu islemler once queue tablosuna alinacak, sonra cron ile islenecektir.

## Hosting Provisioning Kurali

Hosting sadece ResellerClub provider ile calisir. DomainNameAPI hosting icin asla kullanilmayacaktir.

Izinli hosting queue action degerleri:

| Action | Provider | Not |
|---|---|---|
| `hosting/create` | resellerclub | Siparis sonrasi queue |
| `hosting/renew` | resellerclub | Odeme dogrulandiktan sonra queue |
| `hosting/suspend` | resellerclub | Endpoint dogrulaninca adapter tamamlanacak |
| `hosting/unsuspend` | resellerclub | Endpoint dogrulaninca adapter tamamlanacak |
| `hosting/details` | resellerclub | Endpoint dogrulaninca adapter tamamlanacak |

Odeme alinmadan `domain/renew`, `tr_domain/renew` veya `hosting/renew` provider API cagrisi yapilmaz. Odeme yoksa servis `payment_required` durumuna alinir, billing event `renewal_payment_required` olarak yazilir ve notification queue uzerinden musteri bildirimi hazirlanir.

ResellerClub hosting endpointleri resmi kaynakla dogrulanmadigi surece adapter gercek API cagrisi yapmaz; TODO mesaji dondurur ve queue retry/failed akisi calisir.

## SSL Provisioning Kurali

SSL sadece ResellerClub provider ile calisir. DomainNameAPI SSL icin asla kullanilmayacaktir.

Izinli SSL queue action degerleri:

| Action | Provider | Not |
|---|---|---|
| `ssl/create` | resellerclub | Siparis sonrasi queue |
| `ssl/renew` | resellerclub | Odeme dogrulandiktan sonra queue |
| `ssl/reissue` | resellerclub | Endpoint dogrulaninca adapter tamamlanacak |
| `ssl/cancel` | resellerclub | Endpoint dogrulaninca adapter tamamlanacak |
| `ssl/details` | resellerclub | Endpoint dogrulaninca adapter tamamlanacak |
| `ssl/download` | resellerclub | Endpoint dogrulaninca adapter tamamlanacak |
| `ssl/validation_status` | resellerclub | Details endpointinden DCV/verification bilgisi okunur |

Odeme alinmadan `ssl/renew` provider API cagrisi yapilmaz. Odeme yoksa servis `payment_required` durumuna alinir, billing event `renewal_payment_required` olarak yazilir ve notification queue uzerinden musteri bildirimi hazirlanir.

Engine 15 dogrulanmis SSL endpointleri:

| Adapter | ResellerClub path | Method | Not |
|---|---|---|---|
| `createSsl()` | `/api/sslcert/add.json` | POST | `domain-name`, `months`, `customer-id`, `plan-id`, `invoice-option` gerekir |
| `reissueSsl()` | `/api/sslcert/reissue.json` | POST | CSR gerekir; CSR yoksa kontrollu failure |
| `cancelSsl()` | `/api/sslcert/delete.json` | POST | `order-id` gerekir |
| `getSslDetails()` | `/api/sslcert/details.json` | GET | `order-id` gerekir |
| `downloadSsl()` | `/api/sslcert/get-cert-details.json` | GET | Ham certificate response sanitize edilir |
| `getValidationStatus()` | `/api/sslcert/details.json` | GET | DCV bilgisi details response alanlarindan okunur |

Kontrollu TODO kalan SSL endpointleri:

| Adapter | Durum |
|---|---|
| `renewSsl()` | Legacy/WebPro path goruldu, guncel ResellerClub parametre kontrati kesinlesmedigi icin production cagrisi yapmaz |

DomainNameAPI icin `ssl/create`, `ssl/renew`, `ssl/reissue`, `ssl/cancel`, `ssl/details`, `ssl/download` ve `ssl/validation_status` action degerleri sozlesme disidir ve reddedilir.

CSR/private key/certificate raw degerleri loglanmayacak ve kalici queue payload tasarimi netlesmeden manager tarafindan lifecycle options icinden temizlenecektir.

## Admin SSL Mapping Kurali

Admin mapping backend sadece local DB/pricing mapping gunceller. Controller veya template icinden provider API cagrisi yapilmaz.

Zorunlu alanlar:

- `id_product`
- `provider_code=resellerclub`
- `provider_product_id`
- `ssl_product_type`
- `billing_cycle`
- `cost_price`
- `sale_price`
- `currency`
- `active`

Mapping kaydi `NtRcSslProductMappingManager` uzerinden gecer ve satis/maliyet alanlari Engine 11 `NtRcPricingManager` satirina yansitilir. Yeni pricing sistemi yazilmaz.

## Fiyat Kurali

Tum domain, hosting ve SSL satis fiyati hesaplari merkezi fiyat motorundan veya manuel mapping altyapisindan gecmelidir.

Hosting urunleri `ntresellerclub_hosting_product_mapping` icindeki manuel mapping ve fiyat alanlarindan calisir. ResellerClub hosting fiyat API endpointi dogrulanmadan fiyat sync yazilmayacaktir.

SSL urunleri `ntresellerclub_ssl_product_mapping` ile provider product id'ye baglanir ve satis fiyatlari Engine 11 `NtRcPricingManager` altyapisindan gelir. Yeni pricing sistemi yazilmayacaktir.

## Notification Kurali

Mail gonderimi controller, provisioning, provider adapter, operation queue action, renewal scan veya service status akisi icinde dogrudan yapilmayacaktir.

Dogru akis:

```text
Event -> NtRcNotificationEngine -> ntresellerclub_notification_queue -> Cron -> Mail::Send
```

## Guvenlik Kurali

Loglanmasi yasak alanlar:

- ResellerClub API Key
- DomainNameAPI kullanici adi / sifre
- Lisans anahtari
- Musteri odeme verisi
- Auth code
- Token
- Credential
- CSR
- Private key
- Certificate raw
- Raw request

Provider response ve queue response sanitize edilmelidir.

## Production Readiness Kurali

Engine 16 sonrasi backend readiness kontrolu `NtRcProductionReadinessVerifier::summary()` ile lokal olarak okunabilir. Bu kontrol provider API cagrisi yapmaz; sadece sozlesme, queue zinciri, pricing, billing, notification, monitoring, runtime ve schema sorumlulugu kontrollerini yapar.

Production'a cikmadan once:

- `NtRcProductionReadinessVerifier::summary()` basarisiz check dondurmemelidir.
- ResellerClub SSL renew endpoint kontrati dogrulanmadan `renewSsl()` gercek API cagrisi yapmamalidir.
- DomainNameAPI SSL/hosting aksiyonlari sozlesme disi kalmalidir.
- Runtime table creation SQL manager siniflarinda bulunmamalidir.
- Mail ve provider API islemleri queue/cron disina cikmamalidir.

## Gelistirme Kontrol Listesi

1. Bu islem ResellerClub API'de gercekten var mi?
2. Bu islem DomainNameAPI'de gercekten var mi?
3. Islem queue uzerinden mi calisiyor?
4. Shared hostingde 500 hatasi riski var mi?
5. Hata loglanirken credential temizleniyor mu?
6. Mail varsa notification queue uzerinden mi gidiyor?
7. Fiyat hesaplamasi merkezi pricing veya manuel mapping kurallarina uyuyor mu?
8. Schema degisikligi installer/migration guard uzerinden mi yapiliyor?

Bu sorulardan biri olumsuzsa kod uretime alinmayacaktir.
