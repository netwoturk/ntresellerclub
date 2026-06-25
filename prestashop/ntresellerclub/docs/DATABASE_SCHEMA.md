# NetwoTürk ResellerClub Modülü - Final Veritabanı Şeması V1

Bu doküman ntresellerclub modülünün final veritabanı mimarisini tanımlar. Kod geliştirirken tablo adları, alanlar ve ilişkiler bu plana göre ilerlemelidir.

## 1. Provider Tanımları

Tablo: `PREFIX_ntresellerclub_provider`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_provider | INT | Primary key |
| provider_code | VARCHAR(64) | resellerclub, domainnameapi |
| provider_name | VARCHAR(128) | Provider adı |
| provider_type | VARCHAR(64) | domain, hosting, ssl, mixed |
| is_enabled | TINYINT | Aktif/pasif |
| is_licensed | TINYINT | Lisans durumu |
| config_json | MEDIUMTEXT | Hassas alanları temizlenmiş provider ayarları |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

Kural: Provider dışı servis çalıştırılamaz.

## 2. Provider Customer Mapping

Tablo: `PREFIX_ntresellerclub_provider_customer`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_provider_customer | INT | Primary key |
| id_customer | INT | PrestaShop müşteri ID |
| provider_code | VARCHAR(64) | resellerclub/domainnameapi |
| provider_customer_id | VARCHAR(128) | Provider tarafındaki müşteri ID; DomainNameAPI contact hazırlığında boş kalır |
| provider_username | VARCHAR(255) | Provider tarafındaki kullanıcı adı/e-posta |
| email | VARCHAR(255) | Provider müşteri maili |
| status | VARCHAR(50) | pending, active, contact_ready, error |
| raw_data | MEDIUMTEXT | Hassas alanları temizlenmiş provider/queue cevabı |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

DomainNameAPI için `customer/create` provider customer account oluşturmaz. Sadece TR domain contact payload hazırlığı yapar ve başarılı olursa mapping `contact_ready` olur.

## 3. Contact Profile

Tablo: `PREFIX_ntresellerclub_contact_profile`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_contact_profile | INT | Primary key |
| id_customer | INT | PrestaShop müşteri ID |
| profile_type | VARCHAR(50) | personal/company |
| company_name | VARCHAR(255) | Firma adı |
| first_name | VARCHAR(128) | Ad |
| last_name | VARCHAR(128) | Soyad |
| tax_number | VARCHAR(64) | VKN |
| tax_office | VARCHAR(128) | Vergi dairesi |
| tc_number | VARCHAR(64) | TC kimlik |
| address | TEXT | Adres |
| city | VARCHAR(128) | Şehir |
| state | VARCHAR(128) | İlçe/eyalet |
| country_iso | VARCHAR(5) | Ülke ISO |
| postcode | VARCHAR(32) | Posta kodu |
| phone | VARCHAR(64) | Telefon |
| email | VARCHAR(255) | E-posta |
| is_default | TINYINT | Varsayılan profil |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

V1 kuralı: 1 müşteri için varsayılan tek contact yeterlidir.

## 4. TLD Routing

Tablo: `PREFIX_ntresellerclub_tld_route`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_tld_route | INT | Primary key |
| tld | VARCHAR(64) | com, net, com.tr vb. |
| provider_code | VARCHAR(64) | resellerclub/domainnameapi |
| is_enabled | TINYINT | Aktif/pasif |
| priority | INT | Öncelik |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

Zorunlu yönlendirme: global domainler ResellerClub, TR domainler DomainNameAPI.

## 5. Domain Sepeti

Tablo: `PREFIX_ntresellerclub_cart_domain`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_cart_domain | INT | Primary key |
| id_cart | INT | PrestaShop cart ID |
| domain_name | VARCHAR(255) | Alan adı |
| provider_code | VARCHAR(64) | Seçilen provider |
| years | INT | Tescil yılı |
| options_json | MEDIUMTEXT | Nameserver, extra vb. |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

## 6. Servisler

Tablo: `PREFIX_ntresellerclub_service`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_service | INT | Primary key |
| id_customer | INT | PrestaShop müşteri |
| id_order | INT | Sipariş ID |
| id_product | INT | Bağlı PrestaShop ürün ID |
| provider_code | VARCHAR(64) | Provider |
| service_type | VARCHAR(50) | domain, tr_domain, hosting, ssl |
| domain_name | VARCHAR(255) | Domain |
| provider_service_id | VARCHAR(128) | Provider servis/domain ID |
| provider_order_id | VARCHAR(128) | Provider sipariş/order ID |
| provider_customer_id | VARCHAR(128) | Provider customer ID varsa |
| provider_contact_id | VARCHAR(128) | Provider contact ID varsa |
| start_date | DATE | Servis başlangıç tarihi |
| expiry_date | DATE | Bitiş tarihi |
| auto_renew | TINYINT | Otomatik yenileme |
| status | VARCHAR(50) | pending, register_waiting, ready, active, suspended, expired, error, cancelled |
| renew_price | DECIMAL | Yenileme fiyatı |
| transfer_price | DECIMAL | Transfer fiyatı |
| restore_price | DECIMAL | Restore fiyatı |
| currency | VARCHAR(10) | Para birimi |
| last_sync | DATETIME | Son senkronizasyon |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

## 7. Operation Queue

Tablo: `PREFIX_ntresellerclub_operation_queue`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_operation_queue | INT | Primary key |
| id_order | INT | Sipariş ID |
| id_customer | INT | Müşteri ID |
| id_service | INT | Servis ID |
| provider_code | VARCHAR(64) | Provider |
| service_type | VARCHAR(50) | domain, tr_domain, hosting, ssl, customer |
| action | VARCHAR(64) | register, transfer, renew, create, details, contact_update |
| priority | INT | 1 kritik, 4 düşük |
| payload_json | MEDIUMTEXT | Provider payload |
| response_json | MEDIUMTEXT | Provider cevabı |
| status | VARCHAR(50) | pending, processing, done, failed |
| retry_count | INT | Deneme sayısı |
| max_retries | INT | Maksimum deneme |
| last_error | TEXT | Son hata |
| lock_token | VARCHAR(128) | Cron lock token |
| locked_at | DATETIME | Lock zamanı |
| processed_at | DATETIME | İşlem bitişi |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

Kural: Ağır API işlemleri doğrudan çalışmaz. Önce bu tabloya eklenir.

## 8. Monitoring & Health

Tablo: `PREFIX_ntresellerclub_provider_health`

Provider bazlı enabled/licensed durumunu, queue sayıları, sanitize edilmiş son hata ve son kontrol zamanını tutar.

Tablo: `PREFIX_ntresellerclub_runtime_health`

Runtime memory, peak memory, batch limit, SAPI, queue pending/processing/failed ve cron zamanını tutar.

Tablo: `PREFIX_ntresellerclub_provider_statistics`

Provider bazlı günlük queue toplamlarını, retry sayılarını ve son başarılı/failed zamanlarını tutar.

## 9. Notification & Mail

Tablo: `PREFIX_ntresellerclub_notification_template`

Dil ve alıcı tipine göre notification template tutar.

Tablo: `PREFIX_ntresellerclub_notification_queue`

Mail gönderiminden önce bekleyen notification job kayıtlarını tutar. Durumlar: `pending`, `processing`, `sent`, `failed`, `cancelled`.

Tablo: `PREFIX_ntresellerclub_notification_log`

Mail denemelerini ve sanitize edilmiş sonucu tutar. Raw provider request, api key, password, auth-code, token veya credential saklamaz.

Kural: Mail gönderimi doğrudan yapılmaz; `ntresellerclub_notification_queue` içine yazılır ve cron sonunda `Mail::Send` ile batch gönderilir.

## 10. Pricing & Currency

Tablo: `PREFIX_ntresellerclub_price`

DomainNameAPI TR domain, ResellerClub global domain, hosting ve SSL maliyet/satış mapping kayıtlarını tutar.

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_price | INT | Primary key |
| provider_code | VARCHAR(64) | domainnameapi/resellerclub |
| product_type | VARCHAR(50) | domain, tr_domain, hosting, ssl |
| code | VARCHAR(100) | com:register, com.tr:renew, hosting:default:create, ssl:default:renew vb. |
| years | INT | Süre/yıl |
| cost_price | DECIMAL(20,6) | Provider/manual maliyet |
| sale_price | DECIMAL(20,6) | Manual satış fiyatı veya admin baz değeri |
| currency | VARCHAR(10) | Maliyet para birimi |
| target_currency | VARCHAR(10) | Satış hedef para birimi |
| margin_mode | VARCHAR(32) | manual, percent, fixed, hybrid |
| margin_percent | DECIMAL(20,6) | Yüzde kar |
| margin_fixed | DECIMAL(20,6) | Sabit kar |
| tax_included | TINYINT | Satış fiyatı KDV dahil mi |
| tax_rate | DECIMAL(10,4) | KDV oranı |
| rounding_mode | VARCHAR(32) | no_round, nearest_1, nearest_5, nearest_10, psychological_99 |
| last_sync | DATETIME | Son maliyet senkronizasyonu |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

Standart hesaplama sonucu `NtRcPricingEngine` tarafından şu alanlarla döner:

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

Tablo: `PREFIX_ntresellerclub_price_history`

| Alan | Açıklama |
|---|---|
| provider_code/product_type/code | Değişen fiyat kaydı |
| old_cost_price/new_cost_price | Eski/yeni maliyet |
| old_sale_price/new_sale_price | Eski/yeni satış |
| currency | Para birimi |
| change_source | dna_sync, pricing_upsert, admin vb. |
| created_at | Değişim zamanı |

Tablo: `PREFIX_ntresellerclub_exchange_rate_history`

Manuel kur değişimlerini tutar. USD -> TRY/EUR/GBP/AZN desteklenir; ileride para birimi bazlı genişletilebilir.

Engine 11 notu: ResellerClub fiyat mapping altyapısı `ntresellerclub_price` içinde hazırdır. Bu engine ResellerClub için doğrulanmamış fiyat API endpoint'i eklemez.

## 11. TR Domain Fiyatları

TR domain maliyet ve satış fiyatı satırları artık merkezi Pricing & Currency yapısını kullanır. DomainNameAPI maliyet sync sadece TR uzantıları için çalışır ve cron/RuntimeGuard akışı bozulmaz.

## 12. Sistem Devam Tablosu

Fiyat geçmişi, manuel kur geçmişi, hosting ürünleri, SSL ürünleri, webhook log, sistem log ve lisans tabloları mevcut engine kurallarına göre korunur.

## 13. BTK CSV Reporting

Feature key: `btk_csv_reporting`

BTK CSV Reporting yeni tablo eklemez. Rapor çıktıları mevcut tablolardan okunur:

- `PREFIX_ntresellerclub_service`
- `PREFIX_ntresellerclub_contact_profile`
- `PREFIX_ntresellerclub_provider_customer`
- `PREFIX_customer`

CSV dosyaları:

- Barındırılan Alan Adları: `service_type = hosting` olan raporlanabilir servisler; aynı domain için tescil servisi varsa kayıt / bitiş tarihi tescil servisinden alınır.
- Tescil Edilen Alan Adları: `service_type IN (domain, tr_domain)` olan, ancak aynı domain için hosting servisi bulunmayan raporlanabilir servisler.

Raporlanabilir servis statüleri:

- `active`
- `ready`
- `suspended`

CSV kolon sırası:

1. alan adı
2. alan adı sahibi
3. iletişim telefonu
4. iletişim e-postası
5. alan adı kayıt tarihi
6. alan adı süresinin dolma tarihi

Başlık satırı yoktur. Her satır 6 kolon olmalıdır. Boş veri için `*` kullanılır. Virgül ve noktalı virgül veri içinde `-` ile değiştirilir. Tarihler `gg.aa.yyyy` formatında üretilir.

Kural: `NTRC_FEATURE_BTK_CSV_REPORTING` / `btk_csv_reporting` aktif değilse admin CSV indirme kapalıdır.

## Final Kurallar

- Global domain = ResellerClub.
- TR domain = DomainNameAPI.
- Hosting = ResellerClub.
- SSL = ResellerClub.
- DomainNameAPI global domain, hosting ve SSL için kullanılmaz.
- Queue olmadan register/renew/transfer/create işlemi yapılmaz.
- Notification queue olmadan mail gönderimi yapılmaz.
- Pricing engine olmadan satış fiyatı hesaplanmaz.
- BTK CSV Reporting premium feature aktif olmadan BTK CSV indirilemez.
- RuntimeGuard olmadan cron/provisioning/sync/notification çalışmaz.
- Monitoring provider API çağrısı yapmaz; cron sonunda düşük maliyetli DB/runtime snapshot olarak çalışır.
