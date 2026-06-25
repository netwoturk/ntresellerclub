# ADR Index - Architecture Decision Records

Bu dosya ntresellerclub projesinde alınan önemli mimari kararların indeksidir.

## ADR-001 - Provider Ayrımı

Karar:

- ResellerClub = global domain, hosting, SSL
- DomainNameAPI = sadece TR domain ve contact/WHOIS

Sebep:

Provider görevleri karışırsa fiyat, provisioning ve API akışları production ortamında hata üretir.

## ADR-002 - Queue Zorunluluğu

Karar:

Ağır işlemler direkt çalışmayacak, queue + cron üzerinden çalışacak.

Sebep:

Paylaşımlı hostinglerde 500/time-out riskini azaltmak.

## ADR-003 - DomainNameAPI Customer Modeli

Karar:

DomainNameAPI için ResellerClub gibi müşteri account oluşturulmayacak. TR domain contact hazırlığı yapılacak ve status `contact_ready` olacak.

Sebep:

DomainNameAPI SDK içinde net customer account API doğrulanmadı. Varsayımsal endpoint yasak.

## ADR-004 - CURRENT_STATUS Zorunluluğu

Karar:

Her görev sonunda `CURRENT_STATUS.md` güncellenecek.

Sebep:

Farklı bilgisayarlardan veya farklı Codex oturumlarından kaldığı yerden devam etmek.

## ADR-005 - DevBook Klasörü

Karar:

Kod dışı ürün, ekran, karar ve iş akışı dokümanları `docs/devbook/` altında tutulacak.

Sebep:

Proje büyüdükçe mimari, ürün ve geliştirme kararlarını dağınık sohbet geçmişinden bağımsız tutmak.
