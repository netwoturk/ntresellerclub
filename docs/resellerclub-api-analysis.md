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

API anahtarı hiçbir zaman GitHub'a yazılmayacak. PrestaShop `Configuration` alanında saklanacak. Daha sonraki sürümlerde şifreli saklama seçeneği eklenecek.
