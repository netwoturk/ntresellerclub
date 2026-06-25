# ResellerClub API Analiz Notları

Ana kaynak: https://manage.resellerclub.com/kb/answer/744

## Kimlik doğrulama

Her API çağrısında şu parametreler zorunludur:

- `auth-userid`: Reseller ID
- `api-key`: API anahtarı
- `lang-pref`: Varsayılan dil, genelde `en`

## IP whitelist

ResellerClub API çağrıları için IP whitelist zorunludur. API isteğini yapan sunucunun dış IP adresi ResellerClub panelinde Settings > API alanına eklenmelidir. IP aralığı değil tekil IP kullanılmalıdır.

NetwoTürk test ortamı için doğrulanan dış IP:

```text
94.73.151.44
```

## Endpoint mantığı

### Genel API

```text
https://httpapi.com/api/{resource}/{action}.json
```

### Domain uygunluk sorgusu

ResellerClub kılavuzuna göre domain availability için özel domaincheck endpoint kullanılmalıdır:

```text
https://domaincheck.httpapi.com/api/domains/available.json
```

Örnek parametreler:

```text
auth-userid=1130459
api-key=***
domain-name=netwoturk
tlds=com
tlds=net
tlds=org
```

## Domain provisioning API doğrulaması

### `domains/register.json`

- HTTP method: `POST`
- Zorunlu ana parametreler: `domain-name`, `years`, `ns`, `customer-id`, `reg-contact-id`, `admin-contact-id`, `tech-contact-id`, `billing-contact-id`, `invoice-option`, `auto-renew`
- Opsiyonel: `purchase-privacy`, `protect-privacy`, `discount-amount`, `purchase-premium-dns`, `attr-nameN`, `attr-valueN`
- Başarılı cevapta domain order ID `entityid`, action durumu `actionstatus`, customer ID `customerid` alanlarından okunabilir.
- Kaynak: https://manage.resellerclub.com/kb/answer/752

### `domains/transfer.json`

- HTTP method: `POST`
- Zorunlu ana parametreler: `domain-name`, `customer-id`, `reg-contact-id`, `admin-contact-id`, `tech-contact-id`, `billing-contact-id`, `invoice-option`, `auto-renew`
- `auth-code` bazı uzantılarda zorunlu, bazı uzantılarda sonradan onay e-postasıyla sağlanabilir.
- Opsiyonel: `ns`, `purchase-privacy`, `protect-privacy`, `purchase-premium-dns`, `attr-nameN`, `attr-valueN`
- Hata cevabında `status=ERROR` döner. Transfer kullanıcı/registry girdisi bekliyorsa ResellerClub dokümanına göre `NoError` dönebilir; adapter bu durumu tekrar denenmesi gereken API hatası saymamalıdır.
- Kaynak: https://manage.resellerclub.com/kb/answer/758

### `domains/renew.json`

- HTTP method: `POST`
- Zorunlu ana parametreler: `order-id`, `years`, `exp-date`, `invoice-option`, `auto-renew`
- Opsiyonel: `purchase-privacy`, `discount-amount`, `purchase-premium-dns`, `attr-nameN`, `attr-valueN`
- Başarılı cevapta domain order ID `entityid`, action durumu `actionstatus`, customer ID `customerid` alanlarından okunabilir.
- Kaynak: https://manage.resellerclub.com/kb/answer/746

## Açık doğrulama notu

ResellerClub domain register/transfer için provider contact ID alanları zorunludur. Bu phase içinde varsayımsal contact create endpointi yazılmadı. Register/transfer queue çalışması için `reg-contact-id`, `admin-contact-id`, `tech-contact-id`, `billing-contact-id` değerleri mevcut payload/options içinden gelmelidir; eksikse adapter kontrollü hata döndürür ve queue retry/failed akışı çalışır.

## HTTP method kuralları

- Okuma / sorgulama: GET
- Kayıt / oluşturma / güncelleme / silme: POST

## Domain status yorumları

- `available`: satın alınabilir
- `regthroughus`: bizde kayıtlı
- `regthroughothers`: başka firmada kayıtlı, transfer önerilebilir
- `unknown`: tekrar dene / registry bağlantısı yok

## Modül içindeki API katmanı

Ana API sınıfı `NtResellerClubApiClient` olmalıdır. Bu sınıf:

- Base URL seçimi yapar
- Auth parametrelerini otomatik ekler
- GET/POST isteklerini yönetir
- JSON cevabı normalize eder
- Hata, HTTP kodu, raw response ve request loglarını döndürür

## Kritik güvenlik notu

API anahtarı, auth-code ve şifreler hiçbir zaman GitHub'a veya loglara açık yazılmayacak. PrestaShop `Configuration` alanında saklanan provider credential değerleri response ve queue loglarından temizlenmelidir.