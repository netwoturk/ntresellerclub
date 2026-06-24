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
| provider_customer_id | VARCHAR(128) | Provider tarafındaki müşteri ID |
| email | VARCHAR(255) | Provider müşteri maili |
| status | VARCHAR(50) | pending, active, error |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

Kural: Her provider için müşteri ayrı map edilir. Aynı müşteri ResellerClub ve DomainNameAPI tarafında farklı ID alabilir.

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
| provider_code | VARCHAR(64) | Provider |
| service_type | VARCHAR(50) | domain, hosting, ssl |
| domain_name | VARCHAR(255) | Domain |
| provider_service_id | VARCHAR(128) | Provider servis ID |
| expiry_date | DATE | Bitiş tarihi |
| auto_renew | TINYINT | Otomatik yenileme |
| status | VARCHAR(50) | pending, ready, active, suspended, error |
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
| action | VARCHAR(64) | register, transfer, renew, create, details |
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

## 8. TR Domain Fiyatları

Tablo: `PREFIX_ntresellerclub_price`

| Alan | Tip | Açıklama |
|---|---|---|
| id_ntresellerclub_price | INT | Primary key |
| provider_code | VARCHAR(64) | domainnameapi |
| product_type | VARCHAR(50) | tr_domain |
| code | VARCHAR(100) | com.tr:register vb. |
| currency | VARCHAR(10) | USD |
| cost_price | DECIMAL | Alış fiyatı |
| sale_price | DECIMAL | Manuel satış fiyatı |
| margin_mode | VARCHAR(50) | manual, percent, fixed, hybrid |
| margin_percent | DECIMAL | Yüzde kar |
| margin_fixed | DECIMAL | Sabit kar |
| last_sync | DATETIME | Son sync |
| created_at | DATETIME | Oluşturma tarihi |
| updated_at | DATETIME | Güncelleme tarihi |

## 9. Fiyat Geçmişi

Tablo: `PREFIX_ntresellerclub_price_history`

Maliyet ve satış fiyatı değişimlerini kayıt altında tutar.

## 10. Manuel Kur Geçmişi

Tablo: `PREFIX_ntresellerclub_exchange_rate_history`

Manuel kur değişimlerinin geçmişini tutar.

## 11. Hosting Ürünleri

Tablo: `PREFIX_ntresellerclub_hosting_product`

| Alan | Açıklama |
|---|---|
| id_ntresellerclub_hosting_product | Primary key |
| provider_code | resellerclub |
| provider_product_id | ResellerClub ürün ID |
| package_name | Paket adı |
| billing_cycle | Aylık/yıllık |
| cost_price | Maliyet |
| sale_price | Satış |
| currency | Para birimi |
| active | Aktif/pasif |

Kural: Hosting sadece ResellerClub üzerinden yönetilir.

## 12. SSL Ürünleri

Tablo: `PREFIX_ntresellerclub_ssl_product`

SSL ürünleri sadece ResellerClub provider üzerinden yönetilir.

## 13. Webhook Log

Tablo: `PREFIX_ntresellerclub_webhook_log`

Provider webhook event kayıtları için kullanılır.

## 14. Sistem Log

Tablo: `PREFIX_ntresellerclub_log`

Modül içi hata, bilgi ve uyarı loglarını tutar.

## 15. Lisans

Tablo: `PREFIX_ntresellerclub_license`

NetwoTürk yıllık lisans ve feature erişimlerini tutar.

## Final Kurallar

- Global domain = ResellerClub.
- TR domain = DomainNameAPI.
- Hosting = ResellerClub.
- SSL = ResellerClub.
- DomainNameAPI global domain, hosting ve SSL için kullanılmaz.
- Queue olmadan register/renew/transfer/create işlemi yapılmaz.
- RuntimeGuard olmadan cron/provisioning/sync çalışmaz.
