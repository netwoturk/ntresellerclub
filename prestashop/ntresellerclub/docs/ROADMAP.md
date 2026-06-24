# NetwoTürk ntresellerclub Roadmap

Bu dosya ResellerClub + DomainNameAPI destekli PrestaShop domain/hosting/SSL modülünün geliştirme yol haritasıdır.

## Temel Ürün Kuralı

- Global domainler ResellerClub üzerinden yönetilir.
- Hosting servisleri ResellerClub üzerinden yönetilir.
- SSL servisleri ResellerClub üzerinden yönetilir.
- TR uzantılı domainler DomainNameAPI üzerinden yönetilir.
- DomainNameAPI bu modülde global domain, hosting veya SSL için kullanılmaz.
- Ağır işlemler doğrudan API çağrısı yapmaz; queue + cron üzerinden çalışır.
- Shared hosting ortamında 500 hatasını azaltmak için RuntimeGuard ve batch limit zorunludur.

## PHASE 01 - Core Module

Durum: Devam ediyor

### Hedefler

- Modül iskeleti
- Installer sistemi
- SQL kurulum dosyaları
- Ana admin ayar ekranı
- Runtime ayarları
- Log sistemi
- Lisans anahtarı alanı

### Kabul Kriterleri

- Modül PrestaShop 1.7/8/9 üzerinde kurulabilir.
- Kurulumda temel tablolar oluşur.
- Admin panelde ayarlar kaydedilir.
- 500 hatasına karşı RuntimeGuard çalışır.

## PHASE 02 - Provider Architecture

Durum: Devam ediyor

### Hedefler

- Provider interface
- ResellerClub adapter
- DomainNameAPI adapter
- Provider factory
- Provider registry
- API contract guard
- TLD routing

### Kabul Kriterleri

- .com/.net/.org gibi global domainler ResellerClub'a gider.
- .tr/.com.tr/.net.tr gibi TR domainler DomainNameAPI'ye gider.
- Provider dışı aksiyonlar engellenir.
- Controller içinden doğrudan provider API çağrısı yapılmaz.

## PHASE 03 - Domain Search & Cart

Durum: Devam ediyor

### Hedefler

- Domain arama motoru
- TLD bazlı provider seçimi
- DomainNameAPI TR domain fiyat gösterimi
- Domain sepet sistemi
- Sepete domain ekleme controller'ı
- Çoklu yıl seçimi

### Kabul Kriterleri

- Müşteri domain araması yapabilir.
- Uygun domainler provider bazlı ayrılır.
- TR domainlerde manuel kur + fiyat motoru çalışır.
- Domain sepet kaydı PrestaShop cart ID ile eşleşir.

## PHASE 04 - Price & Currency Engine

Durum: Devam ediyor

### Hedefler

- DomainNameAPI USD maliyet senkronizasyonu
- Manuel kur sistemi
- USD -> TRY/EUR/USD dönüşüm altyapısı
- TR domain fiyat tablosu
- Manuel/yüzde/sabit/hibrit kar modeli
- Fiyat geçmişi
- Kur geçmişi

### Kabul Kriterleri

- DomainNameAPI maliyetleri sadece TR uzantıları için çekilir.
- Manuel kur güncellenebilir.
- Satış fiyatı admin panelde hesaplanmış görünür.
- Fiyat değişimleri geçmişe yazılır.

## PHASE 05 - Queue Engine

Durum: Başladı

### Hedefler

- Operation queue tablosu
- Queue manager
- Queue processor
- Retry sistemi
- Failed status
- Queue lock
- Priority sistemi
- Queue cleanup
- Admin queue dashboard

### Kabul Kriterleri

- Register/renew/transfer/create işlemleri doğrudan çalışmaz.
- Queue kayıtları cron ile batch halinde işlenir.
- Başarısız işlemler max retry sonrasında failed olur.
- Aynı queue kaydı iki cron tarafından aynı anda işlenmez.

## PHASE 06 - Provider Customer & Contact System

Durum: Başladı

### Hedefler

- Provider customer mapping
- Contact profile tablosu
- Kişisel/kurumsal contact ayrımı
- ResellerClub customer create queue
- DomainNameAPI customer/contact create queue
- TR domain contact validation
- Varsayılan contact seçimi

### Kabul Kriterleri

- Aynı PrestaShop müşterisi için provider bazlı ayrı müşteri ID tutulur.
- Her domain siparişinde önce mapping kontrol edilir.
- Mapping yoksa customer create queue açılır.
- TR domain için zorunlu contact alanları kontrol edilir.

## PHASE 07 - Domain Provisioning

Durum: Planlandı

### Hedefler

- Domain register queue
- Domain transfer queue
- Domain renew queue
- Nameserver update
- Contact update
- Provider service ID kaydı
- Expiry date sync

### Kabul Kriterleri

- Domain işlemleri queue üzerinden provider adapter'a gider.
- Başarılı işlem sonrası servis tablosu güncellenir.
- Hatalar loglanır ve kullanıcıya ham API cevabı gösterilmez.

## PHASE 08 - Hosting Provisioning

Durum: Planlandı

### Hedefler

- ResellerClub hosting ürün eşleştirme
- PrestaShop ürün -> provider product mapping
- Hosting create queue
- Hosting renew queue
- Hosting suspend/unsuspend
- Hosting servis ekranı

### Kabul Kriterleri

- Hosting sadece ResellerClub adapter üzerinden çalışır.
- Hosting oluşturma sipariş anında değil queue üzerinden olur.
- Servis durumu müşteri panelinde görünür.

## PHASE 09 - SSL Provisioning

Durum: Planlandı

### Hedefler

- ResellerClub SSL ürün eşleştirme
- SSL create queue
- SSL renew queue
- CSR/validation alanları
- SSL servis ekranı

### Kabul Kriterleri

- SSL sadece ResellerClub adapter üzerinden çalışır.
- SSL işlemleri queue üzerinden yürür.

## PHASE 10 - Admin Panel V1

Durum: Planlandı

### Hedefler

- Provider durumu paneli
- Runtime ayar paneli
- Manuel kur paneli
- TR fiyat paneli
- Queue yönetimi
- Failed queue retry butonu
- Log görüntüleme
- Servis listesi

### Kabul Kriterleri

- Admin tek ekrandan provider, fiyat, queue ve log durumunu izleyebilir.
- Failed queue manuel yeniden denenebilir.

## PHASE 11 - Customer Panel V1

Durum: Planlandı

### Hedefler

- Hizmetlerim ekranı
- Domainlerim
- Hostinglerim
- SSL servislerim
- Yenileme bilgisi
- Nameserver görüntüleme
- Contact profile düzenleme

### Kabul Kriterleri

- Müşteri kendi aktif/pending servislerini görebilir.
- Hassas provider verileri açık gösterilmez.

## PHASE 12 - Licensing System

Durum: Planlandı

### Hedefler

- Core lisans
- ResellerClub adapter lisansı
- DomainNameAPI adapter lisansı
- Hosting Manager lisansı
- SSL Manager lisansı
- Lisans doğrulama client
- Feature bazlı erişim

### Kabul Kriterleri

- Lisans yoksa core dışı feature çalışmaz.
- Feature lisansı yoksa ilgili menü/panel/API kapalı olur.

## PHASE 13 - Multilanguage

Durum: Planlandı

### Hedefler

- Türkçe
- İngilizce
- Almanca
- Fransızca
- İspanyolca
- İtalyanca
- Mail template çevirileri
- Admin ve front çevirileri

### Kabul Kriterleri

- Sabit metinler kod içinde dağınık kalmaz.
- Mail ve müşteri paneli çoklu dil destekler.

## PHASE 14 - Production Test

Durum: Planlandı

### Hedefler

- Sandbox/test mode denemeleri
- Shared hosting testleri
- Büyük sepet testi
- Queue retry testi
- Cron testi
- Kur/fiyat testi
- TR domain yönlendirme testi
- Global domain yönlendirme testi

### Kabul Kriterleri

- 500 hatası oluşturan ağır işlem kalmaz.
- API sözleşmesi dışı çağrı yapılmaz.
- DomainNameAPI ve ResellerClub görevleri karışmaz.
