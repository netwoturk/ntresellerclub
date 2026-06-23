# Adapter ve Coklu Dil Mimarisi

Bu proje coklu provider adapter ve coklu dil destekli SaaS urun olarak gelistirilecektir.

## Temel provider kapsami

Bu ana modulde provider kapsami net ayrilacaktir.

| Provider | Kapsam | Fiyat Kaynagi |
|---|---|---|
| ResellerClub | Global domainler, hosting, ileride SSL | ResellerClub panelindeki satis/maliyet yapisi |
| DomainNameAPI | Sadece TR uzantili domainler | DomainNameAPI alis fiyatlari + modul satis fiyat tablosu |

## TLD routing ana kural

DomainNameAPI tarafinda global uzantilar olsa bile ana modulde global uzantilar satilmayacaktir. Global uzantilar ResellerClub uzerinden, TR uzantilari DomainNameAPI uzerinden calisacaktir.

| Uzanti Grubu | Provider |
|---|---|
| com, net, org, info, biz | resellerclub |
| tr, com.tr, net.tr, org.tr, av.tr, gen.tr, web.tr | domainnameapi |

## Hosting ve SSL kuralı

Hosting satisi sadece ResellerClub uzerinden yapilacaktir. DomainNameAPI tarafinda hosting veya SSL urunleri olsa bile bu ana modulde aktif edilmeyecektir. DomainNameAPI icin ileride ayri bir PrestaShop modulu hazirlanabilir.

## Adapter mimarisi

Ana modul sabit kalir: `ntresellerclub`.

Providerlar tak-cikar adapter mantigiyla calisir.

| Katman | Gorev |
|---|---|
| Core | Siparis, servis, musteri, cron, lisans ve musteri paneli |
| Provider Interface | Her provider'in uymasi gereken ortak metotlar |
| Provider Registry | Kurulu/aktif/lisansli provider listesini tutar |
| Provider Factory | Provider koduna gore dogru adapter sinifini uretir |
| Provider Router | TLD veya servis tipine gore dogru provider'i secer |
| Feature License | Provider veya ozellik lisansli mi kontrol eder |

## Provider genisletme kurali

Yeni bir domain/hosting firmasi ekleneceginde core modul degistirilmeden yeni provider sinifi eklenmelidir. Ancak ana ticari kural bozulmayacaktir: global domain ve hosting ResellerClub, TR domain DomainNameAPI.

```text
providers/
- NtRcResellerClubProvider.php
- NtRcDomainNameApiProvider.php
- NtRcOpenProviderProvider.php
- NtRcNamecheapProvider.php
- NtRcCustomProvider.php
```

## Provider kayit tablosu

`ps_ntresellerclub_provider` tablosu provider bilgilerini dinamik tutmak icin kullanilir.

| Alan | Aciklama |
|---|---|
| `provider_code` | resellerclub, domainnameapi, openprovider vb. |
| `provider_name` | Admin panelde gorunen ad |
| `provider_type` | domain, hosting, ssl, mixed |
| `is_enabled` | Admin aktif/pasif durumu |
| `is_licensed` | Lisans sunucusundan gelen yetki |
| `config_json` | Provider'a ozel ayarlar |

## Fiyat mimarisi

| Provider | Kural |
|---|---|
| ResellerClub | Modul fiyat hesaplamasi yapmaz; ResellerClub panel/super site satis yapisi esas alinir |
| DomainNameAPI | Sadece TR uzanti alis fiyatlari cekilir, admin panelde son kullanici satis fiyati belirlenir |

DomainNameAPI fiyat panelinde su kolonlar tutulacaktir:

| Uzanti | Doviz | Tescil | Transfer | Yenileme | Kurtarma | Trustee | Backorder | Satis Modu | Satis Fiyati |
|---|---|---:|---:|---:|---:|---:|---:|---|---:|

## Coklu dil destegi

Desteklenecek diller:

| Dil | Kod |
|---|---|
| Turkce | `tr` |
| Ingilizce | `en` |
| Almanca | `de` |
| Ispanyolca | `es` |
| Fransizca | `fr` |
| Italyanca | `it` |

## Dil kurali

- Musteri panelindeki tum metinler PrestaShop ceviri sistemi uzerinden yonetilir.
- Mail sablonlari her dil icin ayri klasorde tutulur.
- API hata mesajlari normalize edilip modulun dil anahtarlariyla gosterilir.
- Provider donen teknik hata mesajlari admin loglarinda ham olarak saklanir, musteriye sade mesaj gosterilir.

## Mail sablon klasorleri

```text
mails/tr/
mails/en/
mails/de/
mails/es/
mails/fr/
mails/it/
```

## Satis modeliyle baglanti

Core lisans aktifse modul acilir. Provider lisansi yoksa o provider'a ait TLD ve servisler pasif gosterilir. Modul tamamen kilitlenmez; sadece lisanssiz ozellikler kapatilir.
