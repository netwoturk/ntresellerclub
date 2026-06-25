# NetwoTürk ResellerClub / DomainNameAPI API Contract Rules

Bu dosya modül mimarisinde uyulacak zorunlu API sınırlarını tanımlar. Amaç, ResellerClub API, DomainNameAPI API ve PrestaShop modül gereksinimlerinin dışına çıkmadan üretim ortamında güvenli çalışan bir yapı kurmaktır.

## 1. Temel Mimari Kuralı

Modül hiçbir provider için rastgele endpoint, varsayımsal parametre veya dokümanda olmayan işlem kullanmayacaktır.

Tüm provider çağrıları şu katmanlardan geçmelidir:

1. Front / Admin Controller
2. Service / Manager sınıfı
3. Queue Manager
4. Provider Adapter
5. Resmi API Client

Controller içinden doğrudan ResellerClub veya DomainNameAPI çağrısı yapılmayacaktır.

## 2. Provider Ayrımı

| Servis | Provider | Kural |
|---|---|---|
| Global domain | ResellerClub | .com, .net, .org vb. global uzantılar |
| Hosting | ResellerClub | Hosting işlemleri sadece ResellerClub |
| SSL | ResellerClub | SSL işlemleri sadece ResellerClub |
| TR domain | DomainNameAPI | .tr, .com.tr, .net.tr, .org.tr vb. |

DomainNameAPI üzerinden hosting, SSL veya global domain satışı bu modülde aktif edilmeyecektir.

## 3. TLD Routing Kuralı

Her domain işlemi önce TLD Routing tablosundan provider seçmelidir.

Yanlış örnek:

```php
$provider = 'domainnameapi';
```

Doğru örnek:

```php
$provider = NtRcTldRouteManager::resolve($tld);
```

Bu sayede .com.tr DomainNameAPI'ye, .com ResellerClub'a gider.

## 4. API İstekleri Queue Üzerinden Çalışmalıdır

Aşağıdaki işlemler doğrudan çalıştırılmayacaktır:

| İşlem | Direkt API çağrısı |
|---|---|
| Domain register | Yasak |
| Domain transfer | Yasak |
| Domain renew | Yasak |
| Hosting create | Yasak |
| SSL create | Yasak |
| Provider customer create | Yasak |
| Mail gönderimi | Yasak |

Bu işlemler önce ilgili queue tablosuna alınacak, sonra cron ile işlenecektir.

## 5. Shared Hosting Güvenlik Kuralı

Ağır işlemler mutlaka `NtRcRuntimeGuard` ile başlamalıdır.

```php
NtRcRuntimeGuard::beforeHeavyProcess('context_name');
```

Cron, queue, provisioning, price sync, renewal ve notification mail işlemlerinde batch limit uygulanmalıdır.

Varsayılan limitler:

| Ayar | Varsayılan |
|---|---|
| Memory limit | 512M |
| Time limit | 120 saniye |
| Cron batch limit | 10 |
| Maksimum batch limit | 25 |

## 6. Fiyat Kuralı

Tüm domain, hosting ve SSL satış fiyatı hesapları merkezi fiyat motorundan geçmelidir.

Doğru akış:

```text
Provider/manual cost -> NtRcPricingManager -> NtRcPricingEngine -> standart fiyat sonucu
```

DomainNameAPI TR domain fiyatlarını USD maliyet olarak verebilir. Ağır fiyat çekme işlemi cron ve RuntimeGuard dışına çıkarılmayacaktır. ResellerClub global domain, hosting ve SSL fiyatları için bu engine varsayımsal API endpoint eklemez; sadece doğrulanmış veya manuel maliyetlerin yazılacağı mapping altyapısını hazırlar.

Desteklenen fiyat modları:

| Mod | Açıklama |
|---|---|
| manual | Admin satış fiyatını kendisi girer |
| percent | Maliyete yüzde kar eklenir |
| fixed | Maliyete sabit kar eklenir |
| hybrid | Sabit + yüzde kar uygulanır |

Desteklenen rounding modları:

| Mod | Açıklama |
|---|---|
| no_round | Sadece para hassasiyetine yuvarlar |
| nearest_1 | En yakın 1 birime yuvarlar |
| nearest_5 | En yakın 5 birime yuvarlar |
| nearest_10 | En yakın 10 birime yuvarlar |
| psychological_99 | Vergi dahil görünür fiyatı .99 formatına taşır |

Standart fiyat sonucu şu alanları içermelidir:

- `cost_price`
- `cost_currency`
- `converted_cost`
- `target_currency`
- `margin_amount`
- `tax_amount`
- `sale_price_without_tax`
- `sale_price_with_tax`
- `rounding_mode`
- `final_sale_price`

Fiyat geçmişi `ntresellerclub_price_history`, kur geçmişi `ntresellerclub_exchange_rate_history` içine yazılmaya devam edecektir.

## 7. Çoklu Para Birimi Kuralı

Fiyat motoru sadece TRY için sabitlenmeyecektir. Varsayılan hedef para birimi PrestaShop varsayılan para biriminden alınmalıdır.

Manuel kur sistemi şu para birimlerini destekler:

| Kur | Kural |
|---|---|
| USD -> TRY | Desteklenir |
| USD -> EUR | Desteklenir |
| USD -> GBP | Desteklenir |
| USD -> AZN | Desteklenir |

Genişleme hedefleri:

| Dil | Para Birimi |
|---|---|
| Türkçe | TRY |
| İngilizce | USD |
| Almanca | EUR |
| Fransizca | EUR |
| İspanyolca | EUR |
| İtalyanca | EUR |

## 8. Hata Yönetimi Kuralı

API hataları kullanıcıya ham API çıktısı olarak gösterilmemelidir.

Zorunlu yapı:

```php
array(
  'success' => false,
  'message' => 'Kullanıcı dostu hata',
  'error' => 'Teknik hata'
)
```

Teknik hata `NtRcLog` içine yazılmalıdır.

## 9. PrestaShop Uyumluluk Kuralı

Modül PrestaShop 1.7 / 8 / 9 uyumluluğu hedeflerken şu kurallara uymalıdır:

- `_PS_VERSION_` kontrolü olmayan PHP dosyası bırakılmayacak.
- Admin formlarında token yapısı korunacak.
- Controller içinde doğrudan echo yerine güvenli JSON veya template kullanılacak.
- SQL tablolarında `PREFIX_` kullanılacak.
- Çoklu mağaza ve çoklu dil yapısı bozulmayacak.
- Hook işlemleri mümkün olduğunca hafif tutulacak.

## 10. Güvenlik Kuralı

API key, şifre, lisans anahtarı veya provider credential loglara açık yazılmayacaktır.

Loglanması yasak alanlar:

- ResellerClub API Key
- DomainNameAPI kullanıcı adı / şifre
- Lisans anahtarı
- Müşteri ödeme verisi
- Auth code
- Token
- Credential
- Raw request

## 11. Notification & Mail Kuralı

Mail gönderimi controller, provisioning, provider adapter, operation queue action, renewal scan veya service status akışı içinde doğrudan yapılmayacaktır.

Doğru akış:

```text
Event -> NtRcNotificationEngine -> ntresellerclub_notification_queue -> Cron -> Mail::Send
```

Zorunlu kurallar:

- Mail body, subject, variables ve notification log değerleri sanitize edilecektir.
- Customer, admin ve technical_admin alıcı tipleri ayrılacaktır.
- Mail gönderimi `NtRcRuntimeGuard::cronBatchLimit()` ile batch çalışacaktır.
- Notification queue status değerleri `pending`, `processing`, `sent`, `failed`, `cancelled` dışına çıkmayacaktır.
- Retry `retry_count`, `max_retries`, `last_error` alanlarıyla yönetilecektir.
- `NtRcRenewalManager` doğrudan `Mail::Send` veya `renewal_reminder` kullanmayacak; expiry event'leri notification queue'ya yazılacaktır.
- Domain register/transfer/renew başarıları `domain_registered`, `domain_transfer_started`, `domain_renewed` template key'leriyle queue'ya yazılacaktır.
- Servis status değişimleri `service_suspended` ve `service_expired` için queue üzerinden müşteri bildirimi oluşturabilir.
- Notification enqueue hatası provider queue başarısını geri almamalı; hata sanitize edilerek warning seviyesinde loglanmalıdır.

## 12. Geliştirme Kuralı

Yeni özellik eklenirken önce şu kontrol yapılmalıdır:

1. Bu işlem ResellerClub API'de gerçekten var mı?
2. Bu işlem DomainNameAPI'de gerçekten var mı?
3. PrestaShop modül mimarisinde doğru hook/controller katmanı mı kullanılıyor?
4. İşlem queue üzerinden mi çalışıyor?
5. Shared hostingde 500 hatası riski var mı?
6. Hata loglanıyor mu?
7. Çoklu para birimi ve çoklu dil bozuluyor mu?
8. Mail gönderimi varsa notification queue üzerinden mi gidiyor?
9. Fiyat hesaplaması merkezi pricing engine sonucunu mu kullanıyor?

Bu sorulardan biri olumsuzsa kod üretime alınmayacaktır.
