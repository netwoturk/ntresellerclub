# 08 - Licensing Architecture

## Amaç

Modül NetwoTürk tarafından yıllık lisanslı satılabilir ürün olarak dağıtılacaktır.

## Lisans Katmanları

| Lisans | Açıklama |
|---|---|
| Core | Temel modül, ayarlar, runtime, log |
| ResellerClub Adapter | Global domain + provider customer |
| DomainNameAPI Adapter | TR domain + contact hazırlığı |
| Hosting Manager | ResellerClub hosting yönetimi |
| SSL Manager | ResellerClub SSL yönetimi |
| Queue Engine | Ağır işlem kuyruğu |
| Webhook Pack | Provider webhook listener |
| API Gateway | Çoklu provider genişleme |

## Feature Kontrolü

Her kritik ekran ve işlem feature kontrolünden geçmelidir.

Örnek:

```text
DomainNameAPI lisansı yok
  -> TR domain arama kapalı
  -> TR fiyat sync kapalı
  -> TR register queue kapalı
```

## Lisans Kontrol Akışı

```text
Modül açılışı / cron
  -> License check
  -> Feature map
  -> Allowed/Denied
```

## Güvenlik

Lisans anahtarı loglanmaz. Lisans doğrulama response'u sanitize edilmeden kaydedilmez.

## Gelecek Hedef

NetwoTürk lisans sunucusu ile online doğrulama:

```text
license.netwoturk.com
api.netwoturk.com/license
```
