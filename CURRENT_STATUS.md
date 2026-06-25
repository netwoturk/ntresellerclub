# Current Status

Tarih: 2026-06-25

## Son Çalışma

Engine 12 - Hosting Provisioning Engine

## Son Branch

`codex/engine-12-hosting-provisioning`

## Tamamlananlar

- Hosting backend provisioning altyapısı ResellerClub-only olarak hazırlandı.
- `NtRcHostingProductMappingManager` eklendi.
- `NtRcHostingManager` eklendi.
- `NtRcHostingOperationQueueProcessor` eklendi.
- `NtRcResellerClubHostingAdapter` TODO wrapper eklendi.
- `NtRcHostingMonitoring` eklendi.
- PrestaShop product id -> ResellerClub provider product/package mapping tablosu eklendi.
- Sipariş sonrası hosting create doğrudan API çağırmaz; `hosting/create` operation queue oluşturur.
- Provider contract guard hosting action'ları için sadece ResellerClub'a izin verir.
- Hosting lifecycle status seti tamamlandı: `pending`, `provisioning`, `active`, `renewal_due`, `payment_required`, `suspended`, `expired`, `cancelled`, `error`.
- Hosting renew ödeme doğrulanmadan provider queue oluşturmaz; servis `payment_required` durumuna alınır ve notification altyapısına bağlanır.
- Hosting suspend / unsuspend queue action'ları hazırlandı.
- Başarılı hosting create/renew response işleme altyapısı servis kaydını ve notification queue'yu günceller.
- Hosting monitoring summary active hosting count, failed hosting queue ve pending hosting provisioning metriklerini döndürür.
- Hosting fiyatları manuel/mapping tablosu üzerinden çalışır; ResellerClub fiyat API endpointi eklenmedi.
- Admin UI eklenmedi.

## Database Değişikliği

Yeni tablo:

- `ntresellerclub_hosting_product_mapping`

Alanlar:

- `id_product`
- `provider_code`
- `provider_product_id`
- `package_name`
- `billing_cycle`
- `cost_price`
- `sale_price`
- `currency`
- `active`
- `created_at`
- `updated_at`

## TODO

- ResellerClub hosting create/renew/suspend/unsuspend/details endpointleri resmi kaynakla doğrulanınca `NtRcResellerClubHostingAdapter` gerçek API çağrısı yapacak şekilde tamamlanmalıdır.
- Payment provider/invoice entegrasyonu sonraki engine kapsamında `payment_required` akışını gerçek ödeme durumuna bağlamalıdır.

## Bilinen Riskler

- Bu ortamda PHP CLI bulunmadığı için `php -l` çalıştırılamadı.
- Gerçek PrestaShop runtime testi ve gerçek ResellerClub hosting API testi yapılamadı.
- ResellerClub hosting endpointleri doğrulanmadığı için adapter kontrollü TODO/hata döndürür.
- Hosting domain bilgisi product reference veya custom alanlardan standart gelmiyorsa servis `domain_name` boş kalabilir; product mapping yine queue oluşturur.

## Son Test

- Değişen PHP dosyalarında kaba `{}` ve `()` denge kontrolü yapıldı.
- Contract guard içinde DomainNameAPI hosting yasağı korundu.
- Queue action değerleri `hosting/create`, `hosting/renew`, `hosting/suspend`, `hosting/unsuspend` olarak statik kontrol edildi.
- Credential, password, api-key, auth-code, token ve credential sanitize kuralları gözden geçirildi.

## Son Doküman Güncellemesi

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/architecture/15_HOSTING_PROVISIONING_ENGINE.md`
- `docs/database-schema.md`
- `docs/resellerclub-api-analysis.md`
- `prestashop/ntresellerclub/docs/API_CONTRACT_RULES.md`
- `prestashop/ntresellerclub/docs/DATABASE_SCHEMA.md`
- `prestashop/ntresellerclub/docs/ROADMAP.md`
