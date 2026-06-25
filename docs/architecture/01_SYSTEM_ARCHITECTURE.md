# 01 - System Architecture

## Amaç

NetwoTürk `ntresellerclub` modülü, PrestaShop üzerinde domain, hosting ve SSL satış/provisioning süreçlerini yönetmek için geliştirilir.

## Ana Kapsam

- Global domain: ResellerClub
- Hosting: ResellerClub
- SSL: ResellerClub
- TR domain: DomainNameAPI
- Tüm ağır işlemler: Queue + Cron
- Satılabilir lisanslı ürün mimarisi

## Katmanlar

1. PrestaShop Controller
2. Manager / Service Classes
3. Queue Manager
4. Queue Processor
5. Provider Adapter
6. Resmi API Client
7. Log / History / Service tables

## Zorunlu Kural

Controller içinden doğrudan provider API çağrısı yapılmaz.

Doğru akış:

```text
Controller
  -> Manager
  -> Operation Queue
  -> Cron Processor
  -> Provider Adapter
  -> Resmi API
```

## Provider Ayrımı

| Servis | Provider |
|---|---|
| .com / .net / .org | ResellerClub |
| Hosting | ResellerClub |
| SSL | ResellerClub |
| .tr / .com.tr / .net.tr / .org.tr | DomainNameAPI |

## Üretim Hedefi

WHMCS benzeri domain/hosting/SSL yönetimini PrestaShop içine entegre etmek ve NetwoTürk tarafından yıllık lisanslı satılabilir ürün haline getirmek.
