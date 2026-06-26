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

## Fiyat Kurali

Tum domain, hosting ve SSL satis fiyati hesaplari merkezi fiyat motorundan veya manuel mapping altyapisindan gecmelidir.

Hosting urunleri `ntresellerclub_hosting_product_mapping` icindeki manuel mapping ve fiyat alanlarindan calisir. ResellerClub hosting fiyat API endpointi dogrulanmadan fiyat sync yazilmayacaktir.

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
- Raw request

Provider response ve queue response sanitize edilmelidir.

## Gelistirme Kontrol Listesi

1. Bu islem ResellerClub API'de gercekten var mi?
2. Bu islem DomainNameAPI'de gercekten var mi?
3. Islem queue uzerinden mi calisiyor?
4. Shared hostingde 500 hatasi riski var mi?
5. Hata loglanirken credential temizleniyor mu?
6. Mail varsa notification queue uzerinden mi gidiyor?
7. Fiyat hesaplamasi merkezi pricing veya manuel mapping kurallarina uyuyor mu?

Bu sorulardan biri olumsuzsa kod uretime alinmayacaktir.
