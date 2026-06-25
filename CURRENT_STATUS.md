# Current Status

Tarih: 2026-06-25

## Son Engine

Engine 11 - Pricing & Currency Engine Finalization

## Son Branch

`codex/engine-11-pricing-currency-finalization`

## Tamamlananlar

- Merkezi fiyat, kur, kar marjı, KDV ve yuvarlama hesaplaması için `NtRcPricingEngine` eklendi.
- DomainNameAPI TR domain fiyat hesaplama akışı merkezi pricing engine'e bağlandı.
- `NtRcTrPriceCalculator` geriye uyumlu facade olarak korunup merkezi engine'e yönlendirildi.
- `NtRcPricingManager` ile domain, hosting ve SSL fiyat kayıtları için ortak backend manager eklendi.
- ResellerClub global domain / hosting / SSL fiyat mapping altyapısı hazırlandı; doğrulanmamış fiyat API endpoint'i eklenmedi.
- Manuel kur sistemi `USD -> TRY`, `USD -> EUR`, `USD -> GBP`, `USD -> AZN` hedeflerini destekleyecek şekilde genelleştirildi.
- Kar modelleri `manual`, `percent`, `fixed`, `hybrid` olarak korundu.
- KDV dahil / hariç hesaplama ve ileride ülke / para birimi bazlı genişletilebilir vergi oranı desteği eklendi.
- Yuvarlama modları `no_round`, `nearest_1`, `nearest_5`, `nearest_10`, `psychological_99` olarak eklendi.
- Fiyat hesaplama sonucu standart alanlarla döner hale getirildi: `cost_price`, `cost_currency`, `converted_cost`, `target_currency`, `margin_amount`, `tax_amount`, `sale_price_without_tax`, `sale_price_with_tax`, `rounding_mode`, `final_sale_price`.
- Fiyat geçmişi ve kur geçmişi yazımı korunup merkezi manager üzerinden devam edecek şekilde güçlendirildi.
- DomainNameAPI fiyat senkronizasyonunda credential benzeri alanların loglanmaması için hata metni sanitization genişletildi.
- `docs/architecture/14_PRICING_CURRENCY_ENGINE.md` eklendi.
- `ROADMAP.md`, `CHANGELOG.md`, `API_CONTRACT_RULES.md` ve `DATABASE_SCHEMA.md` Engine 11 kapsamıyla güncellendi.

## Database Değişikliği

- Yeni tablo eklenmedi.
- Mevcut `ntresellerclub_price` tablosu genişletildi:
  - `target_currency`
  - `tax_rate`
  - `rounding_mode`
  - `created_at`
  - `updated_at`
- Mevcut `tax_included` alanı korunur; kurulu modüllerde eksikse installer guard tarafından eklenir.
- `ntresellerclub_price_history` ve `ntresellerclub_exchange_rate_history` geçmiş kayıtları korunur.

## Devam Edenler

- Admin UI yazılmadı; Engine 11 sadece backend engine, manager, schema guard ve dokümantasyon kapsamındadır.
- ResellerClub fiyatlarını gerçek API'den senkronize eden endpoint eklenmedi; resmi ve doğrulanmış kontrat netleşene kadar mapping kayıtları manuel / ileride doğrulanmış sync ile doldurulacak.
- Hosting ve SSL tarafında gerçek satış / yenileme akışları ileride ilgili provisioning veya billing engine'lerinde pricing manager'ı kullanacak.
- Ülke bazlı gelişmiş vergi kuralları için altyapı hazır, ancak kapsamlı tax rule tablosu bu engine'de eklenmedi.

## Sıradaki Engine

Öneri: Engine 12 - Hosting Provisioning veya Billing / Payment Required Wiring. Pricing altyapısı hazırlandığı için hosting/SSL satış fiyatlarının gerçek servis akışlarına bağlanması doğal sonraki adımdır.

## Bilinen Riskler

- PHP CLI bu çalışma ortamında bulunmadığı için `php -l` lint çalıştırılamadı.
- Gerçek PrestaShop runtime ve provider sandbox çalıştırması bu ortamda yapılamadı.
- Varsayılan kur değerleri kurulum başlangıcı için placeholder/manual default'tur; production'da admin tarafından güncellenmelidir.
- ResellerClub mapping kayıtları başlangıçta sıfır maliyetli placeholder olarak seed edilir; gerçek satış öncesi manuel veya doğrulanmış sync ile doldurulmalıdır.

## Son Test

- GitHub branch farkı `codex/engine-10-renewal-service-lifecycle-notification...codex/engine-11-pricing-currency-finalization` üzerinden doğrulandı.
- Yeni/değişen PHP dosyalarında kaba `{}` ve `()` dengesi kontrol edildi.
- `NtRcPricingEngine` standart sonuç alanlarının kod ve dokümanda bulunduğu doğrulandı.
- `NtRcManualExchangeRate` içinde desteklenen USD hedefleri `TRY`, `EUR`, `GBP`, `AZN` olarak doğrulandı.
- ResellerClub için yeni ve doğrulanmamış pricing API endpoint'i eklenmediği kontrol edildi.
- DomainNameAPI fiyat senkronizasyon loglarında credential-like değerlerin sanitize edildiği kontrol edildi.
- Credential, password, api-key, auth-code ve token benzeri alanların yeni pricing sınıflarında loglanmadığı tarandı.

## Son Commit

Bu `CURRENT_STATUS.md` kaydı Engine 11 final durumunu temsil eder.

## Son Doküman Güncellemesi

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/architecture/14_PRICING_CURRENCY_ENGINE.md`
- `prestashop/ntresellerclub/docs/API_CONTRACT_RULES.md`
- `prestashop/ntresellerclub/docs/DATABASE_SCHEMA.md`
