# Adapter ve Çoklu Dil Mimarisi

Bu proje çoklu provider adapter ve çoklu dil destekli SaaS ürün olarak geliştirilecektir.

## Adapter mimarisi

Ana modül sabit kalır: `ntresellerclub`.

Providerlar tak-çıkar adapter mantığıyla çalışır.

| Katman | Görev |
|---|---|
| Core | Sipariş, servis, müşteri, cron, lisans ve müşteri paneli |
| Provider Interface | Her provider'ın uyması gereken ortak metotlar |
| Provider Registry | Kurulu/aktif/lisanslı provider listesini tutar |
| Provider Factory | Provider koduna göre doğru adapter sınıfını üretir |
| Provider Router | TLD veya servis tipine göre doğru provider'ı seçer |
| Feature License | Provider veya özellik lisanslı mı kontrol eder |

## Provider genişletme kuralı

Yeni bir domain/hosting firması ekleneceğinde core modül değiştirilmeden yeni provider sınıfı eklenmelidir.

Örnek:

```text
providers/
├─ NtRcResellerClubProvider.php
├─ NtRcDomainNameApiProvider.php
├─ NtRcOpenProviderProvider.php
├─ NtRcNamecheapProvider.php
└─ NtRcCustomProvider.php
```

## Provider kayıt tablosu

`ps_ntresellerclub_provider` tablosu ileride provider bilgilerini dinamik tutmak için kullanılacaktır.

| Alan | Açıklama |
|---|---|
| `provider_code` | resellerclub, domainnameapi, openprovider vb. |
| `provider_name` | Admin panelde görünen ad |
| `provider_type` | domain, hosting, ssl, mixed |
| `is_enabled` | Admin aktif/pasif durumu |
| `is_licensed` | Lisans sunucusundan gelen yetki |
| `config_json` | Provider'a özel ayarlar |

## Çoklu dil desteği

Desteklenecek diller:

| Dil | Kod |
|---|---|
| Türkçe | `tr` |
| İngilizce | `en` |
| Almanca | `de` |
| İspanyolca | `es` |
| Fransızca | `fr` |
| İtalyanca | `it` |

## Dil kuralı

- Müşteri panelindeki tüm metinler PrestaShop çeviri sistemi üzerinden yönetilir.
- Mail şablonları her dil için ayrı klasörde tutulur.
- API hata mesajları mümkünse normalize edilip modülün dil anahtarlarıyla gösterilir.
- Provider dönen teknik hata mesajları admin loglarında ham olarak saklanır, müşteriye sade mesaj gösterilir.

## Mail şablon klasörleri

```text
mails/tr/
mails/en/
mails/de/
mails/es/
mails/fr/
mails/it/
```

## Satış modeliyle bağlantı

Core lisans aktifse modül açılır. Provider lisansı yoksa o provider'a ait TLD ve servisler pasif gösterilir. Modül tamamen kilitlenmez; sadece lisanssız özellikler kapatılır.
