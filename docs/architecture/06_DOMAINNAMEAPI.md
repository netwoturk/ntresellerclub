# 06 - DomainNameAPI Architecture

## Kapsam

DomainNameAPI bu modülde sadece TR uzantılı domainler için kullanılır.

## Desteklenen TLD'ler

- tr
- com.tr
- net.tr
- org.tr
- av.tr
- gen.tr
- web.tr

## Kullanılmayacak Alanlar

| Servis | Durum |
|---|---|
| Global domain | Kullanılmaz |
| Hosting | Kullanılmaz |
| SSL | Kullanılmaz |
| ResellerClub benzeri customer account | Varsayımsal yazılmaz |

## Contact Yaklaşımı

DomainNameAPI tarafında ResellerClub gibi müşteri hesabı açma akışı doğrulanmadığı için `customer/create` queue akışı TR domain contact hazırlığı olarak ele alınır.

Başarılı hazırlık durumu:

```text
provider_customer.status = contact_ready
provider_customer_id = null
```

## SDK Contact Metotları

Doğrulanan SDK metotları:

- `getContacts($domainName)`
- `saveContacts($domainName, $contacts)`

## Register Öncesi Kural

TR domain register işleminden önce contact bilgileri doğrulanmalıdır.

Zorunlu alanlar:

- first_name
- last_name
- email
- phone
- address
- city
- country_iso
- postcode
- personal ise tc_number
- company ise company_name, tax_number, tax_office

## Fiyatlandırma

DomainNameAPI maliyetleri USD olabilir. Satış fiyatı:

```text
USD maliyet
  -> Manuel kur
  -> Hedef para birimi
  -> Kar modeli
  -> Son kullanıcı fiyatı
```

## Güvenlik

DomainNameAPI username/password loglanmaz. SDK response sanitize edilmeden queue response içine yazılmaz.
