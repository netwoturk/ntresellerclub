# Current Status

Tarih: 2026-06-25

## Son Çalışma

Ek Özellik - BTK CSV Reporting Premium Feature

## Son Branch

`codex/feature-btk-csv-reporting`

## Tamamlananlar

- `btk_csv_reporting` premium feature key'i lisans/feature kontrolüne eklendi.
- `NtRcLicense::hasFeature('btk_csv_reporting')` ve `NtRcFeature::isBtkCsvReportingActive()` hazırlandı.
- `NtRcBtkCsvExportEngine` eklendi.
- BTK formatında iki CSV üretim akışı hazırlandı:
  - Barındırılan Alan Adları
  - Tescil Edilen Alan Adları
- CSV çıktısı başlıksız, UTF-8, 6 kolonlu ve `gg.aa.yyyy` tarih formatında üretilecek şekilde hazırlandı.
- Virgül, noktalı virgül, satır sonu ve tab karakterleri CSV verisinden temizlenecek şekilde sanitize edildi.
- Eksik veri için `*` fallback'i kullanıldı.
- Admin modül konfigürasyon ekranına feature aktifken CSV indirme backend köprüsü eklendi.
- Feature aktif değilse admin CSV erişimi kapalı uyarısı verir.
- Büyük veri için batch okuma mantığı eklendi; default batch 500, üst sınır 1000 satırdır.
- `docs/architecture/BTK_CSV_REPORTING_ENGINE.md` eklendi.
- `CHANGELOG.md`, `ROADMAP.md`, `DATABASE_SCHEMA.md` ve kök `docs/database-schema.md` güncellendi.

## Database Değişikliği

- Yeni tablo eklenmedi.
- BTK CSV Reporting şu mevcut tablolardan okur:
  - `ntresellerclub_service`
  - `ntresellerclub_contact_profile`
  - `ntresellerclub_provider_customer`
  - `customer`
- Yeni configuration key:
  - `NTRC_FEATURE_BTK_CSV_REPORTING`

## CSV Kapsamı

Barındırılan Alan Adları:

- `service_type = hosting` olan raporlanabilir servisler.
- Aynı domain için `domain` veya `tr_domain` tescil servisi varsa kayıt ve bitiş tarihi tescil servisinden alınır.
- Sadece hosting hizmeti verilen domainler de listelenir.

Tescil Edilen Alan Adları:

- `service_type IN (domain, tr_domain)` olan raporlanabilir servisler.
- Aynı domain için hosting servisi varsa bu CSV'ye alınmaz.

Raporlanabilir statüler:

- `active`
- `ready`
- `suspended`

## Devam Edenler

- Ayrı gelişmiş admin controller / streaming download controller ileride eklenebilir.
- BTK'nin ileride zorunlu kılabileceği ek kolon veya dosya adlandırma standardı gelirse doküman ve engine güncellenmelidir.

## Sıradaki Engine

Öneri: Engine 12 - Hosting Provisioning veya Billing / Payment Required Wiring. BTK raporlama backend'i hazır olduğu için asıl servis yaşam döngüsü ve faturalama akışlarının tamamlanması doğal sonraki adımdır.

## Bilinen Riskler

- PHP CLI bu çalışma ortamında bulunmadığı için `php -l` lint çalıştırılamadı.
- Gerçek PrestaShop admin runtime testi bu ortamda yapılamadı.
- Telefon bilgisi contact profile içinde yoksa CSV'de `*` döner.
- Hosting-domain eşleşmesi domain adı üzerinden yapılır; farklı yazılmış domain kayıtları ayrı satır kabul edilir.

## Son Test

- GitHub branch farkı `codex/engine-11-pricing-currency-finalization...codex/feature-btk-csv-reporting` üzerinden doğrulandı.
- Yeni/değişen PHP dosyalarında kaba `{}` ve `()` dengesi kontrol edildi.
- CSV zorunlu metotlarının bulunduğu kontrol edildi.
- Feature kapalıyken admin download erişiminin kapalı olduğu statik olarak kontrol edildi.
- Credential, password, api-key, auth-code ve token benzeri alanların CSV export içinde okunmadığı/loglanmadığı tarandı.
- Türkçe admin stringlerinin UTF-8 olarak korunduğu geri okuma ile doğrulandı.

## Son Commit

Bu `CURRENT_STATUS.md` kaydı BTK CSV Reporting premium feature final durumunu temsil eder.

## Son Doküman Güncellemesi

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/architecture/BTK_CSV_REPORTING_ENGINE.md`
- `docs/database-schema.md`
- `prestashop/ntresellerclub/docs/DATABASE_SCHEMA.md`
