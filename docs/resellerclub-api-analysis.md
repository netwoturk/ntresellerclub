# ResellerClub API Analiz Notlari

Ana kaynak: https://manage.resellerclub.com/kb/answer/744

## Kimlik dogrulama

Her API cagrisinda su parametreler zorunludur:

- `auth-userid`: Reseller ID
- `api-key`: API anahtari
- `lang-pref`: Varsayilan dil, genelde `en`

## IP whitelist

ResellerClub API cagrilari icin IP whitelist zorunludur. API istegini yapan sunucunun dis IP adresi ResellerClub panelinde Settings > API alanina eklenmelidir.

## Endpoint mantigi

### Genel API

```text
https://httpapi.com/api/{resource}/{action}.json
```

### Domain uygunluk sorgusu

ResellerClub kilavuzuna gore domain availability icin ozel domaincheck endpoint kullanilmalidir:

```text
https://domaincheck.httpapi.com/api/domains/available.json
```

## Domain provisioning API dogrulamasi

### `domains/register.json`

- HTTP method: `POST`
- Zorunlu ana parametreler: `domain-name`, `years`, `ns`, `customer-id`, `reg-contact-id`, `admin-contact-id`, `tech-contact-id`, `billing-contact-id`, `invoice-option`, `auto-renew`
- Basarili cevapta domain order ID `entityid`, action durumu `actionstatus`, customer ID `customerid` alanlarindan okunabilir.
- Kaynak: https://manage.resellerclub.com/kb/answer/752

### `domains/transfer.json`

- HTTP method: `POST`
- Zorunlu ana parametreler: `domain-name`, `customer-id`, `reg-contact-id`, `admin-contact-id`, `tech-contact-id`, `billing-contact-id`, `invoice-option`, `auto-renew`
- `auth-code` bazi uzantilarda zorunlu, bazi uzantilarda sonradan onay e-postasiyla saglanabilir.
- Kaynak: https://manage.resellerclub.com/kb/answer/758

### `domains/renew.json`

- HTTP method: `POST`
- Zorunlu ana parametreler: `order-id`, `years`, `exp-date`, `invoice-option`, `auto-renew`
- Basarili cevapta domain order ID `entityid`, action durumu `actionstatus`, customer ID `customerid` alanlarindan okunabilir.
- Kaynak: https://manage.resellerclub.com/kb/answer/746

## Acik dogrulama notu

ResellerClub domain register/transfer icin provider contact ID alanlari zorunludur. Varsayimsal contact create endpointi yazilmadi.

## Hosting provisioning dogrulama notu

Engine 12 kapsaminda ResellerClub hosting create, renew, suspend, unsuspend ve details endpoint/resource/action bilgileri bu repository icinde resmi kaynakla dogrulanamadi.

Bu nedenle `NtRcResellerClubHostingAdapter` su metotlarda gercek API cagrisi yapmaz ve kontrollu TODO cevabi dondurur:

- `createHosting()`
- `renewHosting()`
- `suspendHosting()`
- `unsuspendHosting()`
- `getHostingDetails()`

TODO: ResellerClub resmi hosting API path, HTTP method, zorunlu parametreler, response alanlari ve lifecycle hata kodlari dogrulaninca adapter tamamlanmalidir. Dogrulama yapilmadan varsayimsal endpoint eklenmeyecektir.

## SSL provisioning dogrulama notu

Engine 15 kapsaminda ResellerClub SSL dokumanlari tekrar tarandi.

Ana SSL kaynaklari:

- HTTP API genel kurallari: https://manage.resellerclub.com/kb/answer/744
- Authentication ve test/live URL kurallari: https://manage.resellerclub.com/kb/answer/753
- SSL add: https://www.resellerclub.com/help/article/Add-an-SSL-Certificate-Order-Using-the-API
- SSL enroll: https://www.resellerclub.com/help/article/Enroll-SSL-Certificates-via-API
- SSL reissue: https://www.resellerclub.com/help/article/Reissue-SSL-Certificates-Using-the-API
- SSL details: https://manage.resellerclub.com/kb/node/2402
- SSL delete: https://www.resellerclub.com/help/article/Delete-an-SSL-Certificate-Order-Using-the-API
- SSL verification method: https://www.resellerclub.com/help/article/Change-SSL-Certificate-Verification-Method-via-API
- SSL CSR validation: https://www.resellerclub.com/help/article/Validate-an-SSL-CSR-Using-the-API
- SSL product key: https://www.resellerclub.com/help/article/Prouduct-keys-correspond-to-the-product-TLD

### Dogrulanan SSL API pathleri

| Islem | API path | Method | Durum |
|---|---|---|---|
| SSL add / purchase | `/api/sslcert/add.json` | POST | Dogrulandi |
| SSL enroll | `/api/sslcert/enroll.json` | POST | Dogrulandi, CSR gerektirir |
| SSL reissue | `/api/sslcert/reissue.json` | POST | Dogrulandi, CSR gerektirir |
| SSL details | `/api/sslcert/details.json` | GET | Dogrulandi |
| SSL delete/cancel | `/api/sslcert/delete.json` | POST | Dogrulandi |
| SSL change verification method | `/api/sslcert/change-verification-method.json` | POST | Dogrulandi |
| SSL validate CSR | `/api/sslcert/validate-csr.json` | POST | Dogrulandi, CSR gerektirir |

### Koda baglanan SSL adapter metotlari

- `createSsl()` -> `/api/sslcert/add.json`
- `reissueSsl()` -> `/api/sslcert/reissue.json` ancak CSR payload icinde yoksa kontrollu failure doner.
- `cancelSsl()` -> `/api/sslcert/delete.json`
- `getSslDetails()` -> `/api/sslcert/details.json`
- `getValidationStatus()` -> `/api/sslcert/details.json`; DCV durumu `executioninfoparams`, `actionstatus`, `verification_method` gibi response alanlarindan okunacaktir.
- `downloadSsl()` -> `/api/sslcert/get-cert-details.json`; ResellerClub API indexinde "Get Certificate Details" olarak goruldu, WebPro/ResellerClub KB iceriginde path dogrulandi. Ham certificate response log/queue response icinde tutulmamalidir.

### Dogrulanamayan / kontrollu TODO kalan SSL islemleri

- `renewSsl()` icin `/api/sslcert/renew.xml` pathi legacy/WebPro API materyalinde bulundu; ancak guncel ResellerClub help tarafinda tam parametre sozlesmesi kesinlestirilemedi. Bu nedenle `renewSsl()` production API cagrisi yapmaz ve kontrollu TODO/failure cevabi dondurur.
- CSR ham verisi queue payload'inda kalici saklanmamali. Bu nedenle `NtRcSslManager` lifecycle options icinden `csr`, `private_key`, `certificate_raw` benzeri alanlari temizlemeye devam eder. CSR gerektiren enroll/reissue icin sonraki engine'de secure transient input veya encrypted secret tasima tasarimi gereklidir.
- `downloadSsl()` ham certificate alanlarini adapter response sanitization ile temizler; ham certificate DB/log icine yazilmaz.

### SSL product key

ResellerClub product key dokumaninda SSL Certificate product key `sslcert` olarak listelenmistir.

### Guvenlik

SSL adapter, response ve safe payload icinden su alanlari temizler:

- `api-key`
- `password`
- `credential`
- `token`
- `auth-code`
- `csr`
- `private_key`
- `private-key`
- `certificate`
- `certificate_raw`
- `cert_raw`

## HTTP method kurallari

- Okuma / sorgulama: GET
- Kayit / olusturma / guncelleme / silme: POST

## Kritik guvenlik notu

API anahtari, auth-code ve sifreler hicbir zaman GitHub'a veya loglara acik yazilmayacak. Provider credential degerleri response ve queue loglarindan temizlenmelidir.
