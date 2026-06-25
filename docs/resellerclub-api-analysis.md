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

## HTTP method kurallari

- Okuma / sorgulama: GET
- Kayit / olusturma / guncelleme / silme: POST

## Kritik guvenlik notu

API anahtari, auth-code ve sifreler hicbir zaman GitHub'a veya loglara acik yazilmayacak. Provider credential degerleri response ve queue loglarindan temizlenmelidir.
