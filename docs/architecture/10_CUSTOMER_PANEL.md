# 10 - Customer Panel Architecture

## Amaç

Müşterinin kendi domain, hosting ve SSL servislerini PrestaShop hesabı içinde görmesini ve temel işlemleri yönetmesini sağlamak.

## Ana Ekranlar

| Ekran | Görev |
|---|---|
| Hizmetlerim | Tüm servislerin özeti |
| Domainlerim | Domain listesi |
| Hostinglerim | Hosting listesi |
| SSL Servislerim | SSL listesi |
| Contact Profilleri | Kişisel/kurumsal contact yönetimi |
| Yenileme Bilgileri | Bitiş tarihi ve yenileme |
| Nameserver | Görüntüleme / queue ile güncelleme |

## Domain İşlemleri

Müşteri panelinden ağır API işlemi direkt çalışmaz.

Örnek:

```text
Nameserver güncelle
  -> Queue enqueue
  -> Cron
  -> Provider adapter
```

## Görünecek Servis Durumları

| Durum | Açıklama |
|---|---|
| pending | İşlem bekliyor |
| processing | İşlem sürüyor |
| active | Aktif |
| ready | Hazır |
| suspended | Askıda |
| error | Hata var |
| expired | Süresi doldu |

## Güvenlik

Müşteriye provider iç ID, API cevabı, credential veya auth-code gösterilmez.

## Çoklu Dil

Müşteri paneli Türkçe, İngilizce, Almanca, Fransızca, İspanyolca ve İtalyanca destekleyecek şekilde hazırlanmalıdır.
