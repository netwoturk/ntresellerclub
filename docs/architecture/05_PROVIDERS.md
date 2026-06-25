# 05 - Provider Architecture

## Amaç

ResellerClub ve DomainNameAPI entegrasyonlarını birbirine karıştırmadan yönetmek.

## Provider Sorumlulukları

| Provider | Sorumluluk |
|---|---|
| ResellerClub | Global domain, hosting, SSL, customer account |
| DomainNameAPI | Sadece TR domain ve domain contact/WHOIS |

## Katmanlar

- `NtRcProviderInterface`
- `NtRcResellerClubProvider`
- `NtRcDomainNameApiProvider`
- `NtRcProviderFactory`
- `NtRcProviderRegistry`
- `NtRcApiContractGuard`

## API Contract Guard

Her queue enqueue ve processor dispatch aşamasında provider/action/servis uygunluğu kontrol edilir.

Engellenen örnekler:

- DomainNameAPI ile global domain
- DomainNameAPI ile hosting
- DomainNameAPI ile SSL
- Tanımsız provider action
- Controller içinden direkt API çağrısı

## Provider Adapter Kuralı

Adapter içine varsayımsal endpoint yazılamaz.

Emin olunmayan API noktaları:

- TODO olarak bırakılır.
- İlgili docs dosyasına not düşülür.
- Üretim akışına bağlanmaz.

## Güvenlik

Provider response sanitize edilmelidir.

Temizlenecek alanlar:

- api-key
- password/passwd
- auth-code/auth_code/AuthCode
- token
- credential
- raw request url
