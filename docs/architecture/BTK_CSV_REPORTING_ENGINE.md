# BTK CSV Reporting Engine

Tarih: 2026-06-25

## Amaç

`btk_csv_reporting` premium feature paketi, BTK talebine uygun iki başlıksız CSV çıktısı üretir:

- Barındırılan Alan Adları
- Tescil Edilen Alan Adları

CSV çıktıları API çağrısı yapmaz. Mevcut servis, contact profile, provider customer ve PrestaShop müşteri kayıtlarından okunur.

## Feature Key

Feature key: `btk_csv_reporting`

Konfigürasyon anahtarı: `NTRC_FEATURE_BTK_CSV_REPORTING`

Erişim kuralı:

- `NtRcFeature::isBtkCsvReportingActive()` false ise admin panelindeki BTK CSV indirme kapalıdır.
- `NtRcLicense::hasFeature('btk_csv_reporting')` mevcut lisans/feature guard noktasıdır.
- Feature varsayılan olarak kapalı gelir; premium lisans/ayar aktif edilmeden CSV indirilemez.

## Backend Class

Class: `NtRcBtkCsvExportEngine`

Metotlar:

- `exportHostedDomainsCsv($offset = 0, $limit = null)`
- `exportRegisteredOnlyDomainsCsv($offset = 0, $limit = null)`
- `buildCsvRow($row)`
- `sanitizeCsvValue($value)`
- `formatBtkDate($value)`
- `validateSixColumns($columns)`

Ek yardımcılar sadece class içinde protected olarak tutulur.

## CSV Formatı

Başlık satırı yoktur.

Kolon sırası kesinlikle:

1. alan adı
2. alan adı sahibi
3. iletişim telefonu
4. iletişim e-postası
5. alan adı kayıt tarihi
6. alan adı süresinin dolma tarihi

Her satır tam 6 kolondur.

Boş veri için `*` kullanılır.

Virgül ve noktalı virgül veri içinde `-` ile değiştirilir. Satır sonu ve tab karakterleri boşlukla değiştirilir. Türkçe karakterler dönüştürülmez; çıktı UTF-8 olarak üretilir.

Tarih formatı: `gün.ay.yıl`

Örnek:

```csv
ornek.com,Ali Veli,*,ali@example.com,15.11.2026,15.11.2027
barindirma.net,NetwoTürk Ltd,02120000000,teknik@example.com,*,15.11.2026
```

## Barındırılan Alan Adları

Kaynak: `ntresellerclub_service`

Kural:

- `service_type = hosting` olan ve raporlanabilir statüdeki domainler listelenir.
- Aynı domain için `domain` veya `tr_domain` tescil kaydı varsa kayıt ve bitiş tarihi tescil servisinden alınır.
- Sadece hosting hizmeti varsa tarih bilgileri hosting servisinden alınır.
- Domain bazında tek satır üretilir.

Raporlanabilir statüler:

- `active`
- `ready`
- `suspended`

## Tescil Edilen Alan Adları

Kaynak: `ntresellerclub_service`

Kural:

- `service_type IN (domain, tr_domain)` olan ve raporlanabilir statüdeki domainler listelenir.
- Aynı domain için raporlanabilir `hosting` servisi varsa bu CSV'ye alınmaz.
- Domain bazında tek satır üretilir.

## İletişim Verisi Önceliği

Alan adı sahibi:

1. `contact_profile.company_name`
2. `contact_profile.first_name + last_name`
3. PrestaShop customer adı
4. `provider_customer.provider_username`
5. `*`

Telefon:

1. `contact_profile.phone`
2. `*`

E-posta:

1. `contact_profile.email`
2. `provider_customer.email`
3. PrestaShop customer email
4. `*`

## Admin Download

Modül konfigürasyon ekranında backend indirme köprüsü hazırlanmıştır.

- Feature aktif değilse panel sadece kapalı uyarısı gösterir.
- Feature aktifse iki CSV butonu sunulur.
- İndirme `text/csv; charset=UTF-8` header'ı ile yapılır.
- Başlık satırı ve BOM eklenmez.

## Büyük Veri ve Shared Hosting

Export engine varsayılan 500 satırlık batch ile veriyi parça parça okur. `NTRC_BTK_EXPORT_BATCH_LIMIT` tanımlanırsa 1-1000 aralığında kullanılır.

`NtRcRuntimeGuard::beforeHeavyProcess('btk_csv_export')` çağrısı ile memory/time ayarları mevcut runtime politikasına göre uygulanır.

## Güvenlik

CSV export provider API çağrısı yapmaz ve credential, api-key, auth-code, password veya token alanlarını okumaz. Provider raw_data kullanılmaz.

## Veritabanı

Yeni tablo eklenmez.

Okunan tablolar:

- `ntresellerclub_service`
- `ntresellerclub_contact_profile`
- `ntresellerclub_provider_customer`
- `customer`

## Bilinen Sınırlar

- Contact profile yoksa telefon alanı `*` döner.
- Hosting-domain eşleşmesi domain adı üzerinden yapılır.
- Çok büyük mağazalarda streaming controller ileride ayrı admin controller olarak genişletilebilir.
