# Veritabani Semasi

Bu dosya NetwoTurk ResellerClub PrestaShop modulunun WHMCS benzeri servis yonetimi icin kullandigi tablolari aciklar.

## `ps_ntresellerclub_customer`

PrestaShop musterisi ile ResellerClub customer kaydini eslestirir.

| Alan | Aciklama |
|---|---|
| `id_customer` | PrestaShop musteri ID |
| `resellerclub_customer_id` | ResellerClub customer ID |
| `email` | Musteri e-posta |
| `phone` | Telefon |
| `company` | Firma |
| `status` | pending / active / error |

## `ps_ntresellerclub_contact`

Domain sahibi/administrative/technical contact verilerini tutar.

## `ps_ntresellerclub_service`

Musterinin satin aldigi domain, hosting, SSL ve e-posta servislerini takip eder.

| Alan | Aciklama |
|---|---|
| `service_type` | domain / tr_domain / hosting / ssl / email |
| `domain_name` | Alan adi |
| `provider_order_id` | Provider order ID |
| `provider_service_id` | Provider service ID |
| `start_date` | Baslangic tarihi |
| `expiry_date` | Bitis tarihi |
| `status` | pending / provisioning / active / renewal_due / payment_required / expired / suspended / cancelled / error |
| `renew_price` | Yenileme ucreti |
| `transfer_price` | Transfer ucreti |
| `restore_price` | Kurtarma ucreti |

## `ps_ntresellerclub_hosting_product_mapping`

PrestaShop hosting urunlerini ResellerClub hosting paketleriyle eslestirir. Hosting sadece ResellerClub uzerinden calisir.

| Alan | Aciklama |
|---|---|
| `id_product` | PrestaShop product id |
| `provider_code` | Sabit `resellerclub` |
| `provider_product_id` | ResellerClub urun/paket ID |
| `package_name` | Paket adi |
| `billing_cycle` | monthly / quarterly / semiannual / yearly / biennial / triennial |
| `cost_price` | Manuel maliyet |
| `sale_price` | Manuel satis fiyati |
| `currency` | Para birimi |
| `active` | Aktif mapping |

## `ps_ntresellerclub_cart_domain`

Domain arama modulunden gelen ve sepete baglanan domain secimini tutar.

## `ps_ntresellerclub_price`

Domain/TR domain/hosting/SSL manuel veya provider kaynakli fiyat mapping kayitlarini tutar. Engine 12 hosting fiyatlari manual/mapping tabanlidir; varsayimsal ResellerClub fiyat API endpointi eklenmedi.

## `ps_ntresellerclub_notice`

Yenileme bildirimlerinin tekrar gonderilmesini onler.

## `ps_ntresellerclub_log`

API, cron ve provisioning loglari.

## `ps_ntresellerclub_license`

Yillik SaaS lisans takibi.

## BTK CSV Reporting

Premium feature key: `btk_csv_reporting`

Yeni tablo eklemez. CSV raporlari `ps_ntresellerclub_service`, `ps_ntresellerclub_contact_profile`, `ps_ntresellerclub_provider_customer` ve `ps_customer` kayitlarindan uretilir.
