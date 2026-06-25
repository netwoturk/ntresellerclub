# NetwoTürk ResellerClub Modülü - Final Veritabanı Şeması V1

Bu doküman ntresellerclub modülünün final veritabanı mimarisini tanımlar. Kod geliştirirken tablo adları, alanlar ve ilişkiler bu plana göre ilerlemelidir.

## 1. Provider Tanımları

Tablo: `PREFIX_ntresellerclub_provider`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_provider | INT | Primary key |
| code | VARCHAR(64) | resellerclub, domainnameapi |
| name | VARCHAR(128) | Provider adı |
| type | VARCHAR(64) | domain, hosting, ssl, mixed |
| active | TINYINT | Aktif/pasif |
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
| active | TINYINT | Aktif/pasif |
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
| service_type | VARCHAR(50) | domain, hosting, ssl |
| domain_name | VARCHAR(255) | Domain |
| provider_service_id | VARCHAR(128) | Provider servis/domain ID |
| provider_order_id | VARCHAR(128) | Provider sipariş/order ID |
| provider_customer_id | VARCHAR(128) | Provider customer ID varsa |
| provider_contact_id | VARCHAR(128) | Provider contact ID varsa |
| start_date | DATE | Servis başlangıç tarihi |
| expiry_date | DATE | Bitiş tarihi |
| auto_renew | TINYINT | Otomatik yenileme |
| status | VARCHAR(50) | pending, register_waiting, ready, active, suspended, error |
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

| Alan | Tip | Açıklama |
|---|---|---|
| provider_code | VARCHAR(64) | Provider kodu |
| status | VARCHAR(32) | ok, warning, disabled, unlicensed |
| queue_pending | INT | Provider pending queue sayısı |
| queue_failed | INT | Provider failed queue sayısı |
| last_error | TEXT | Sanitize edilmiş son hata |
| response_time_ms | INT | Health snapshot ölçüm süresi |
| checked_at | DATETIME | Kontrol zamanı |

Tablo: `PREFIX_ntresellerclub_runtime_health`

Runtime memory, peak memory, batch limit, SAPI, queue pending/processing/failed ve cron zamanını tutar.

Tablo: `PREFIX_ntresellerclub_provider_statistics`

Provider bazlı günlük queue toplamlarını, retry sayılarını ve son başarılı/failed zamanlarını tutar.

## 9. Notification & Mail

Tablo: `PREFIX_ntresellerclub_notification_template`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_notification_template | INT | Primary key |
| template_key | VARCHAR(100) | Bildirim türü |
| lang_iso | VARCHAR(5) | tr, en, de, fr, es, it |
| recipient_type | VARCHAR(32) | customer, admin, technical_admin |
| subject | VARCHAR(255) | Mail konusu |
| body_html | MEDIUMTEXT | HTML gövde |
| body_text | MEDIUMTEXT | Text gövde |
| is_active | TINYINT | Aktif/pasif |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

Tablo: `PREFIX_ntresellerclub_notification_queue`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_notification_queue | INT | Primary key |
| template_key | VARCHAR(100) | Bildirim türü |
| lang_iso | VARCHAR(5) | Mail dili |
| recipient_type | VARCHAR(32) | customer, admin, technical_admin |
| id_customer | INT | Müşteri ID, varsa |
| id_service | INT | Servis ID, varsa |
| to_email | VARCHAR(255) | Alıcı e-posta |
| to_name | VARCHAR(255) | Alıcı adı |
| subject | VARCHAR(255) | Render edilmiş konu |
| body_html | MEDIUMTEXT | Render edilmiş HTML gövde |
| body_text | MEDIUMTEXT | Render edilmiş text gövde |
| variables_json | MEDIUMTEXT | Sanitize edilmiş template değişkenleri |
| dedupe_key | VARCHAR(191) | Tekrar bildirim engeli |
| priority | INT | 1 kritik, 4 düşük |
| status | VARCHAR(32) | pending, processing, sent, failed, cancelled |
| retry_count | INT | Deneme sayısı |
| max_retries | INT | Maksimum deneme |
| last_error | TEXT | Sanitize edilmiş son hata |
| lock_token | VARCHAR(128) | Cron lock token |
| locked_at | DATETIME | Lock zamanı |
| available_at | DATETIME | Gönderime uygun zaman |
| sent_at | DATETIME | Gönderim zamanı |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

Tablo: `PREFIX_ntresellerclub_notification_log`

Mail denemelerini ve sanitize edilmiş sonucu tutar. Raw provider request, api key, password, auth-code, token veya credential saklamaz.

Notification template key listesi:

- domain_registered
- domain_transfer_started
- domain_renewed
- domain_expiring_30
- domain_expiring_15
- domain_expiring_7
- domain_expiring_1
- hosting_created
- hosting_renewed
- ssl_created
- ssl_renewed
- queue_failed_admin
- provider_down_admin
- payment_required
- service_suspended
- service_expired

Kural: Mail gönderimi doğrudan yapılmaz; `ntresellerclub_notification_queue` içine yazılır ve cron sonunda `Mail::Send` ile batch gönderilir.

## 10. TR Domain Fiyatları

Tablo: `PREFIX_ntresellerclub_price`

TR domain maliyet ve satış fiyatı satırlarını tutar. Fiyat motoru DomainNameAPI maliyetlerini sadece TR uzantıları için işler.

## 11. Sistem Devam Tablosu

Fiyat geçmişi, manuel kur geçmişi, hosting ürünleri, SSL ürünleri, webhook log, sistem log ve lisans tabloları mevcut engine kurallarına göre korunur.

## Final Kurallar

- Global domain = ResellerClub.
- TR domain = DomainNameAPI.
- Hosting = ResellerClub.
- SSL = ResellerClub.
- DomainNameAPI global domain, hosting ve SSL için kullanılmaz.
- Queue olmadan register/renew/transfer/create işlemi yapılmaz.
- Notification queue olmadan mail gönderimi yapılmaz.
- RuntimeGuard olmadan cron/provisioning/sync/notification çalışmaz.
- Monitoring provider API çağrısı yapmaz; cron sonunda düşük maliyetli DB/runtime snapshot olarak çalışır.