# 04 - Runtime Architecture

## Amaç

Modülün paylaşımlı hosting, VPS ve sunucu ortamlarında stabil çalışması.

## RuntimeGuard

Ağır işlemlerden önce çağrılmalıdır:

```php
NtRcRuntimeGuard::beforeHeavyProcess('context_name');
```

## Varsayılan Ayarlar

| Ayar | Varsayılan |
|---|---|
| Memory limit | 512M |
| Time limit | 120 saniye |
| Cron batch limit | 10 |
| Maksimum batch | 25 |

## Kullanılacağı Yerler

- Cron controller
- Operation queue processor
- Pending provisioning
- DomainNameAPI price sync
- Renewal scan
- Bulk service sync
- Büyük import/export işlemleri

## Kural

Hook işlemleri hafif tutulmalıdır. Ağır işler hook içinde çalışmaz; queue'ya yazılır.

Yanlış:

```text
Order hook -> API register
```

Doğru:

```text
Order hook -> Queue enqueue -> Cron -> API register
```

## Hata Dönüşü

Front controller hata alırsa 500 yerine JSON dönmelidir:

```php
array('success' => false, 'message' => 'İşlem sırasında hata oluştu.')
```

Teknik detay `NtRcLog` içine yazılır.
