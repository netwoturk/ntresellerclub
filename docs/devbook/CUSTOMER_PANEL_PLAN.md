# Customer Panel Plan

Bu doküman müşteri hesabı içindeki ntresellerclub servis ekranlarını tanımlar.

## Ana Menü

```text
Hesabım
└── Hizmetlerim
    ├── Domainlerim
    ├── Hostinglerim
    ├── SSL Servislerim
    ├── Contact Profillerim
    └── Yenilemelerim
```

## 1. Hizmetlerim

Müşteriye tüm servislerin özeti gösterilir.

- Domain sayısı
- Hosting sayısı
- SSL sayısı
- Yaklaşan yenilemeler
- Bekleyen işlemler
- Hatalı işlemler için kullanıcı dostu mesaj

## 2. Domainlerim

Alanlar:

- Domain adı
- Provider
- Status
- Expiry date
- Auto renew
- Nameserver
- Contact status

Müşteri aksiyonları:

- Nameserver görüntüle
- Nameserver güncelleme isteği oluştur
- Contact bilgisi görüntüle
- Yenileme talebi oluştur

## 3. Hostinglerim

Alanlar:

- Domain
- Paket
- Status
- Expiry date
- Yenileme durumu

## 4. SSL Servislerim

Alanlar:

- Domain
- SSL tipi
- Status
- Expiry date
- Validation status

## 5. Contact Profillerim

Profil tipleri:

- Kişisel
- Kurumsal

TR domain için zorunlu bilgiler burada tamamlanır.

## 6. Yenilemelerim

- Yaklaşan yenilemeler
- Payment required
- Expired servisler

## Güvenlik Kuralı

Müşteriye provider iç ID, API cevabı, queue payload, credential veya auth-code gösterilmez.

## İşlem Kuralı

Müşteri panelindeki ağır işlemler direkt API çağrısı yapmaz. Queue kaydı oluşturur.
