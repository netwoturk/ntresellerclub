# Çoklu Domain Provider Mimarisi

Bu proje sadece ResellerClub'a bağlı kalmayacak şekilde tasarlanır. ResellerClub ilk provider olarak kalır; DomainNameAPI ise özellikle `.tr`, `.com.tr`, `.net.tr`, `.org.tr` gibi TRABIS uzantıları için ikinci provider olarak konumlandırılır.

## Provider ayrımı

| Uzantı | Provider | Not |
|---|---|---|
| `.com` | ResellerClub | Varsayılan global domain provider |
| `.net` | ResellerClub | Varsayılan global domain provider |
| `.org` | ResellerClub | Varsayılan global domain provider |
| `.tr` | DomainNameAPI | TRABIS ek alanları gerekir |
| `.com.tr` | DomainNameAPI | TRABIS ek alanları gerekir |
| `.net.tr` | DomainNameAPI | TRABIS ek alanları gerekir |
| `.org.tr` | DomainNameAPI | TRABIS ek alanları gerekir |

## Ana karar

Domain arama, kayıt, yenileme ve transfer işlemlerinde önce domain uzantısı ayrıştırılır. Uzantıya göre doğru provider seçilir. Böylece ResellerClub ve DomainNameAPI verileri birbirine karışmaz.

## Ortak Provider Interface

Her provider aşağıdaki metotları desteklemelidir:

| Metot | Görev |
|---|---|
| `checkAvailability($sld, array $tlds, $period = 1)` | Domain uygunluk sorgusu |
| `registerDomain($domainName, $years, array $contact, array $nameservers, array $extra = array())` | Domain kayıt |
| `renewDomain($domainName, $years)` | Domain yenileme |
| `transferDomain($domainName, $authCode, $years = 1)` | Domain transfer |
| `getDetails($domainName)` | Domain detay |

## DomainNameAPI notları

Yüklenen PHP DNA kütüphanesine göre kullanım:

```php
$dna = new \DomainNameApi\DomainNameAPI_PHPLibrary($username, $password, $testmode);
```

Domain uygunluk:

```php
$dna->checkAvailability(['netwoturk'], ['com.tr'], 1, 'create');
```

TRABIS domain kayıtlarında ek parametreler gerekir:

- `TRABISDOMAINCATEGORY`
- `TRABISCITIZIENID` veya ticari kayıt alanları
- `TRABISNAMESURNAME`
- `TRABISCOUNTRYID` veya `TRABISCOUNTRYNAME`
- `TRABISCITYID` veya `TRABISCITYNAME`

## Veri karışmasını önleyen kurallar

| Kural | Açıklama |
|---|---|
| `provider_code` zorunlu | Her hizmet hangi provider üzerinden açıldıysa o kaydedilir |
| Provider bazlı order id | `provider_order_id` her provider'ın kendi ID bilgisidir |
| Provider bazlı contact id | TRABIS contact ile ResellerClub contact ayrı tutulur |
| Provider bazlı renewal | Yenileme geldiğinde domain hangi provider'daysa onun API'si çağrılır |
| Provider bazlı price | Fiyat listeleri provider bazında ayrılır |

## Örnek

| Domain | Provider | İşlem |
|---|---|---|
| `netwoturk.com` | `resellerclub` | ResellerClub domaincheck ve register |
| `netwoturk.com.tr` | `domainnameapi` | DomainNameAPI checkAvailability ve registerWithContactInfo |
