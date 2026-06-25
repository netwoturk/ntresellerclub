# NetwoTurk ResellerClub Modulu - Veritabani Semasi

Bu dokuman ntresellerclub modulunun temel veritabani mimarisini tanimlar.

## Provider Tanimlari

Tablo: `PREFIX_ntresellerclub_provider`

Provider kayitlari `resellerclub` ve `domainnameapi` ayrimini tutar. Hosting icin tek izinli provider `resellerclub` olmalidir.

## Provider Customer Mapping

Tablo: `PREFIX_ntresellerclub_provider_customer`

PrestaShop musterisi ile provider customer/contact hazirlik kayitlarini eslestirir.

## Contact Profile

Tablo: `PREFIX_ntresellerclub_contact_profile`

Musteri contact profilini tutar.

## TLD Routing

Tablo: `PREFIX_ntresellerclub_tld_route`

Global domainler ResellerClub, TR domainler DomainNameAPI tarafina yonlenir.

## Servisler

Tablo: `PREFIX_ntresellerclub_service`

| Alan | Aciklama |
|---|---|
| `id_customer` | PrestaShop musteri |
| `id_order` | Siparis ID |
| `id_product` | Bagli PrestaShop urun ID |
| `provider_code` | Provider |
| `service_type` | domain, tr_domain, hosting, ssl |
| `domain_name` | Domain |
| `provider_service_id` | Provider servis/domain ID |
| `provider_order_id` | Provider siparis/order ID |
| `start_date` | Servis baslangic tarihi |
| `expiry_date` | Bitis tarihi |
| `status` | pending, provisioning, register_waiting, ready, active, renewal_due, payment_required, suspended, expired, error, cancelled |
| `renew_price` | Yenileme fiyati |
| `currency` | Para birimi |

## Operation Queue

Tablo: `PREFIX_ntresellerclub_operation_queue`

Agir API islemleri dogrudan calismaz. Once bu tabloya eklenir.

Hosting action degerleri:

- `hosting/create`
- `hosting/renew`
- `hosting/suspend`
- `hosting/unsuspend`

## Hosting Product Mapping

Tablo: `PREFIX_ntresellerclub_hosting_product_mapping`

PrestaShop hosting urunlerini ResellerClub paket/maliyet/satis mapping kayitlarina baglar. Hosting fiyatlari bu manuel/mapping tablosundan calisir; varsayimsal ResellerClub fiyat API endpoint'i kullanilmaz.

| Alan | Aciklama |
|---|---|
| `id_product` | PrestaShop product id |
| `provider_code` | Sabit `resellerclub` |
| `provider_product_id` | ResellerClub urun/paket ID |
| `package_name` | Paket adi |
| `billing_cycle` | monthly, quarterly, semiannual, yearly, biennial, triennial |
| `cost_price` | Manuel maliyet |
| `sale_price` | Manuel satis fiyati |
| `currency` | Para birimi |
| `active` | Aktif mapping |

## Monitoring & Health

`NtRcHostingMonitoring::summary()` su metrikleri dashboard/monitoring katmani icin okunabilir hale getirir:

- `active_hosting_count`
- `failed_hosting_queue`
- `pending_hosting_provisioning`

## Notification & Mail

Mail gonderimi dogrudan yapilmaz; `ntresellerclub_notification_queue` icine yazilir ve cron sonunda batch gonderilir.

Engine 12 hosting create/renew basarilari `hosting_created` ve `hosting_renewed` template key'leriyle notification queue'ya baglidir.

## Final Kurallar

- Global domain = ResellerClub.
- TR domain = DomainNameAPI.
- Hosting = ResellerClub.
- SSL = ResellerClub.
- DomainNameAPI global domain, hosting ve SSL icin kullanilmaz.
- Queue olmadan register/renew/transfer/create islemi yapilmaz.
- Notification queue olmadan mail gonderimi yapilmaz.
- Pricing engine veya manuel mapping olmadan satis fiyati hesaplanmaz.
