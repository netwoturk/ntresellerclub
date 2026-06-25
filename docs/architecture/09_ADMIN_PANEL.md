# 09 - Admin Panel Architecture

## Amaç

Admin panel, provider ayarları, fiyat motoru, queue yönetimi, servisler ve logları tek yerden yönetmelidir.

## Ana Bölümler

| Bölüm | Görev |
|---|---|
| Provider Durumu | ResellerClub / DomainNameAPI aktiflik |
| API Ayarları | Credential kayıtları |
| Runtime Ayarları | Memory, time, batch limit |
| Kur Motoru | Manuel kur yönetimi |
| TR Fiyat Paneli | DomainNameAPI TR fiyatları |
| Queue Yönetimi | Pending/processing/done/failed kayıtlar |
| Failed Retry | Hatalı kayıtları tekrar deneme |
| Servisler | Domain/hosting/SSL servis listesi |
| Loglar | Sistem hata/bilgi kayıtları |
| Lisans | Feature lisansları |

## Admin Panel Kuralı

Admin ekranı ağır işlem çalıştırmaz. Butonlar sadece queue oluşturur veya güvenli batch işlem başlatır.

## Failed Queue

Failed queue için admin aksiyonları:

- Tekrar dene
- Hata detayını gör
- Log kaydını gör
- Manuel kapat

## Güvenlik

API key ve şifre alanları maskelenmelidir. Kaydedilen credential tekrar açık gösterilmemelidir.

## Çoklu Dil

Admin metinleri çeviri yapısına uygun yazılmalıdır. Kod içine dağınık sabit metin bırakılmamalıdır.
