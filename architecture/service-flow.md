# Servis Akış Mimarisi

## Domain Arama Akışı

1. Müşteri ana sayfadaki domain arama kutusuna alan adını yazar.
2. `ntdomainsearch` modülü Ajax isteği gönderir.
3. Modül `ntresellerclub` API ayarlarını okur.
4. ResellerClub domaincheck endpoint sorgulanır.
5. Sonuç uygun ise satın alma butonu gösterilir.
6. Domain bilgisi sepete özel veri olarak eklenir.

## Sipariş Otomasyon Akışı

1. PrestaShop siparişi ödeme onayı alır.
2. `ntresellerclub` hook devreye girer.
3. Müşterinin ResellerClub customer id kaydı aranır.
4. Yoksa ResellerClub customer oluşturulur.
5. Domain için contact kaydı oluşturulur veya mevcut contact kullanılır.
6. Domain register / hosting order / SSL order işlemi yapılır.
7. ResellerClub order id veritabanına kaydedilir.
8. Hizmet başlangıç ve bitiş tarihi hesaplanır.
9. Müşteri panelinde Hizmetlerim alanında gösterilir.

## Yenileme Akışı

1. Günlük cron aktif servisleri kontrol eder.
2. Bitişe 30 / 15 / 7 gün kalanlar tespit edilir.
3. Tekrarlı mail gönderimini önlemek için notice tablosu kontrol edilir.
4. Müşteriye yenileme hatırlatma maili gönderilir.
5. Yenileme siparişi PrestaShop üzerinden oluşturulur.
6. Ödeme sonrası ResellerClub renewal API çağrısı yapılır.

## Lisans Akışı

1. Modül kurulumunda domain normalize edilir.
2. Lisans anahtarı admin panelden girilir.
3. NetwoTürk lisans sunucusuna domain + lisans + modül kodu gönderilir.
4. Sonuç cachelenir.
5. Lisans aktif değilse yeni provisioning işlemleri durdurulur.
6. Mevcut müşteri verileri silinmez, sadece otomasyon kilitlenir.
