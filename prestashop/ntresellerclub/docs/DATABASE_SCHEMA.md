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

Kural: Her provider için müşteri ayrı map edilir. Aynı müşteri ResellerClub ve DomainNameAPI tarafında farklı ID alabilir.

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

V2 hazırlığı: Owner/Admin/Billing/Tech contact ayrımı eklenebilir.

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

Zorunlu yönlendirme:

| Uzantı | Provider |
|---|---|
| com/net/org/info/biz | resellerclub |
| tr/com.tr/net.tr/org.tr/av.tr/gen.tr/web.tr | domainnameapi |

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

Kural: Başarılı domain register/renew işleminden sonra servis `active`, başarılı transfer kuyruğundan sonra `ready` durumuna alınır. Provider cevabında dönen order/service/contact/expiry değerleri hassas alanlar temizlendikten sonra kaydedilir.

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

DomainNameAPI TR register queue işlenmeden önce provider customer mapping `contact_ready` olmalıdır. Hazır değilse önce `customer/create` contact hazırlık kuyruğu çalışır; register kuyruğu retry/failed kurallarını bozmadan bekler.

## 8. Monitoring & Health

Tablo: `PREFIX_ntresellerclub_provider_health`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_provider_health | INT | Primary key |
| provider_code | VARCHAR(64) | Provider kodu |
| status | VARCHAR(32) | ok, warning, disabled, unlicensed |
| is_enabled | TINYINT | Provider aktif mi |
| is_licensed | TINYINT | Provider lisanslı mı |
| queue_pending | INT | Provider pending queue sayısı |
| queue_failed | INT | Provider failed queue sayısı |
| last_error | TEXT | Sanitize edilmiş son hata |
| response_time_ms | INT | Health snapshot ölçüm süresi |
| checked_at | DATETIME | Kontrol zamanı |
| created_at | DATETIME | Kayıt zamanı |

Tablo: `PREFIX_ntresellerclub_runtime_health`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_runtime_health | INT | Primary key |
| context | VARCHAR(64) | cron, admin, manual vb. |
| status | VARCHAR(32) | ok, warning |
| memory_limit | VARCHAR(32) | PHP memory limit |
| memory_usage_bytes | BIGINT | Anlık memory kullanımı |
| memory_peak_bytes | BIGINT | Peak memory kullanımı |
| max_execution_time | INT | PHP execution time |
| batch_limit | INT | RuntimeGuard batch limiti |
| php_sapi | VARCHAR(64) | PHP SAPI |
| queue_pending | INT | Pending queue toplamı |
| queue_processing | INT | Processing queue toplamı |
| queue_failed | INT | Failed queue toplamı |
| last_cron_at | DATETIME | Son cron işaret zamanı |
| checked_at | DATETIME | Kontrol zamanı |
| created_at | DATETIME | Kayıt zamanı |

Tablo: `PREFIX_ntresellerclub_provider_statistics`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_provider_statistics | INT | Primary key |
| provider_code | VARCHAR(64) | Provider kodu |
| metric_date | DATE | Günlük metrik tarihi |
| total_queue | INT | Toplam queue |
| pending_queue | INT | Pending queue |
| processing_queue | INT | Processing queue |
| done_queue | INT | Done queue |
| failed_queue | INT | Failed queue |
| retry_queue | INT | Retry almış queue |
| avg_retry | DECIMAL | Ortalama retry |
| last_success_at | DATETIME | Son başarılı queue zamanı |
| last_failure_at | DATETIME | Son failed queue zamanı |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

Kural: Monitoring Engine cron sonunda otomatik çalışır, provider API çağrısı yapmaz, sadece DB/runtime sinyallerini yazar. Hassas alanlar `last_error`, log ve response çıktılarında sanitize edilir.

## 9. TR Domain Fiyatları

Tablo: `PREFIX_ntresellerclub_price`

TR domain maliyet ve satış fiyatı satırlarını tutar. Fiyat motoru DomainNameAPI maliyetlerini sadece TR uzantıları için işler.

## 10. Sistem Devam Tablosu

Fiyat geçmişi, manuel kur geçmişi, hosting ürünleri, SSL ürünleri, webhook log, sistem log ve lisans tabloları mevcut engine kurallarına göre korunur.

## Final Kurallar

- Global domain = ResellerClub.
- TR domain = DomainNameAPI.
- Hosting = ResellerClub.
- SSL = ResellerClub.
- DomainNameAPI global domain, hosting ve SSL için kullanılmaz.
- Queue olmadan register/renew/transfer/create işlemi yapılmaz.
- RuntimeGuard olmadan cron/provisioning/sync çalışmaz.
- Monitoring provider API çağrısı yapmaz; cron sonunda düşük maliyetli DB/runtime snapshot olarak çalışır.