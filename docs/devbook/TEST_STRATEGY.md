# Test Strategy

Bu doküman ntresellerclub projesinin test ve production doğrulama stratejisini tanımlar.

## Test Katmanları

1. Static check
2. PHP lint
3. Install/upgrade SQL test
4. Queue workflow test
5. Provider adapter mock test
6. Cron test
7. Shared hosting runtime test
8. Production sandbox test

## Zorunlu Testler

### Queue

- Pending -> processing -> done
- Pending -> retry -> failed
- Failed -> retryFailed -> pending
- Priority sıralaması
- Lock token koruması

### Runtime

- Batch limit
- Memory limit
- Long running process
- Cron JSON response

### Provider

- ResellerClub global domain akışı
- DomainNameAPI TR domain akışı
- Provider contract dışı istek reddi

### Notification

- Mail queue
- Retry
- Failed notification
- 6 dil template wrapper

### Monitoring

- Runtime snapshot
- Provider health snapshot
- Queue statistics

### Renewal

- 30/15/7/1 gün hatırlatma
- Duplicate reminder engeli
- Expired status

## Güvenlik Testi

Aşağıdaki değerlerin log/mail/queue response içinde kalmadığı kontrol edilir:

- api-key
- password
- passwd
- token
- auth-code
- credential
- raw request

## Production Öncesi Test

- PrestaShop 1.7
- PrestaShop 8
- PrestaShop 9
- Paylaşımlı hosting
- VPS
- Cron ile batch çalışma
- Büyük queue backlog

## Not

Codex ortamında PHP CLI yoksa bunu raporda belirtir. PHP lint daha sonra local veya CI ortamında çalıştırılır.
