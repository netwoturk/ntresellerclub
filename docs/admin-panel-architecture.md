# Admin Panel ve Yönetim Mimarisi

Bu modül yıllık lisanslı, çoklu provider destekli SaaS ürün olarak geliştirilecektir.

## Admin menü bölümleri

| Bölüm | Görev |
|---|---|
| Dashboard | Genel durum, lisans, provider durumu, servis sayıları |
| Lisans | NetwoTürk yıllık lisans anahtarı ve feature durumu |
| Provider Ayarları | ResellerClub ve DomainNameAPI API bilgileri |
| Uzantı Yönlendirme | Hangi TLD hangi provider ile çalışacak |
| Ürün/Fiyat | Domain, hosting, SSL fiyatları ve kar marjı |
| Servisler | Müşterilerin domain/hosting/SSL hizmetleri |
| Cari Eşleşmeleri | PrestaShop müşteri ile provider cari bağlantıları |
| Yenileme Bildirimleri | 30/15/7/3/1 gün bildirim kayıtları |
| Loglar | API, cron ve provisioning hata kayıtları |

## Lisans feature mantığı

| Feature | Açıklama |
|---|---|
| `core` | Ana modül çalışması |
| `provider_resellerclub` | ResellerClub API aktifliği |
| `provider_domainnameapi` | DomainNameAPI aktifliği |
| `hosting_manager` | Hosting ürünleri ve cPanel bağlantıları |
| `renewal_automation` | Cron ve mail hatırlatma sistemi |
| `advanced_domain_panel` | DNS, nameserver, transfer kodu gibi gelişmiş yönetim |

## Provider davranış kuralı

Modül komple kilitlenmez. Sadece lisansı veya API bilgisi olmayan provider'a ait uzantı/hizmetler kapalı görünür.

| Durum | Davranış |
|---|---|
| ResellerClub aktif, DomainNameAPI pasif | `.com/.net/.org` ve hosting çalışır, `.tr/.com.tr` kapalı görünür |
| DomainNameAPI aktif, ResellerClub pasif | `.tr/.com.tr` çalışır, ResellerClub ürünleri kapalı görünür |
| İkisi aktif | Tüm tanımlı provider hizmetleri çalışır |
| Core lisans pasif | Yeni provisioning durur, mevcut servisler görüntülenir |

## Yönetim paneli hedefi

Admin panel, WHMCS mantığında servis ve provider durumunu tek ekrandan gösterecek; ancak ödeme ve fatura tarafını PrestaShop kendi sistemi yönetecektir.
