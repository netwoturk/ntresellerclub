# 03 - Queue Architecture

## Amaç

Shared hosting ortamlarında 500/time-out hatalarını engellemek ve API işlemlerini güvenli hale getirmek.

## Queue Gerektiren İşlemler

| İşlem | Direkt API |
|---|---|
| Domain register | Yasak |
| Domain transfer | Yasak |
| Domain renew | Yasak |
| Hosting create | Yasak |
| SSL create | Yasak |
| Provider customer create | Yasak |
| Contact update | Queue üzerinden |

## Akış

```text
Order / Admin / Front Action
  -> Queue Manager
  -> Operation Queue Table
  -> Cron Controller
  -> Queue Processor
  -> Provider Adapter
```

## Priority

| Değer | Anlam |
|---|---|
| 1 | Kritik |
| 2 | Yüksek |
| 3 | Normal |
| 4 | Düşük |

Pending kayıtlar `priority ASC, id ASC` işlenir.

## Lock Sistemi

Aynı queue kaydının iki cron tarafından işlenmesini engellemek için `lock_token` kullanılır.

```text
pending
  -> atomic lock
  -> processing + lock_token
  -> provider call
  -> done / pending retry / failed
```

## Retry

- Hatalı işlem retry sayacını artırır.
- `retry_count >= max_retries` ise status `failed` olur.
- Admin panelden `retryFailed($idQueue)` ile tekrar pending yapılabilir.

## Cleanup

30 günden eski `done` kayıtlar `cleanupDone(30)` ile silinebilir.

## Zorunlu Güvenlik

Queue response ve loglarda API key, password, auth-code, token ve credential tutulmaz.
