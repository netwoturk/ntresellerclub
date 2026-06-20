# NetwoTürk ResellerClub WHMCS Integration for PrestaShop

Bu repository, PrestaShop üzerinde WHMCS mantığında çalışan ResellerClub API otomasyon sistemi için oluşturuldu.

## Hedef

PrestaShop mağazasını hosting/domain yönetim paneline dönüştürmek:

- ResellerClub API ile domain uygunluk sorgulama
- ResellerClub ürün/fiyat senkronizasyonu
- PrestaShop siparişi sonrası otomatik müşteri, contact ve servis oluşturma
- Domain, hosting, SSL ve ek hizmet takibi
- Hizmet başlangıç/bitiş tarihi yönetimi
- Yenileme, transfer ve restore ücret/durum takibi
- 30 / 15 / 7 gün kala yenileme hatırlatma mailleri
- Müşteri panelinde Hizmetlerim alanı
- Domain yönetimi ve hosting/cPanel yönlendirme butonları

## Modüller

### `ntresellerclub`
Ana otomasyon modülü. API ayarları, müşteri/contact/order işlemleri, servis takibi, cron ve lisans kontrol katmanı burada yer alır.

### `ntdomainsearch`
Ön yüz domain arama modülü. Ana sayfaya eklenebilir alan adı arama kutusu, Ajax sorgu ve sepete ekleme akışı burada yer alır.

## SaaS ve lisans notu

Bu ürün NetwoTürk tarafından yıllık lisanslı SaaS modül olarak konumlandırılacaktır. Mimari içinde domain bazlı lisans doğrulama, yıllık lisans bitiş tarihi, lisans durumu ve uzaktan lisans kontrol sistemi zorunlu kabul edilir.

## Geliştirme sırası

1. V1: API çekirdeği + domain arama + PrestaShop kurulum iskeleti
2. V2: Sipariş sonrası ResellerClub müşteri/contact/domain otomasyonu
3. V3: Hizmet takip paneli + müşteri Hizmetlerim ekranı
4. V4: Yenileme cronları + 30/15/7 gün mail sistemi
5. V5: Hosting/SSL/cPanel ve gelişmiş WHMCS benzeri yönetim

## ResellerClub doküman ana kaynağı

- Access and Authentication: https://manage.resellerclub.com/kb/answer/744
- Domain availability endpoint: `https://domaincheck.httpapi.com/api/domains/available.json`
