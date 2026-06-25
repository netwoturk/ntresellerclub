# NetwoTürk Workflow Standard

Bu standart, NetwoTürk projelerinde ChatGPT + Codex + GitHub ile çalışırken uygulanacak zorunlu iş akışıdır.

## 1. GitHub Tek Doğru Kaynak

Sohbet geçmişi geçici kabul edilir. Kalıcı referans GitHub repository içindeki doküman ve koddur.

Zorunlu dosyalar:

- `CURRENT_STATUS.md`
- `CHANGELOG.md`
- `ROADMAP.md`
- `docs/`
- `docs/architecture/`
- `docs/devbook/`

## 2. Çoklu Bilgisayar Çalışma Kuralı

İş bilgisayarı, ev bilgisayarı, laptop veya başka bir cihazdan devam edilebilir.

Her yeni oturumda önce:

1. Repository güncellenir.
2. `CURRENT_STATUS.md` okunur.
3. Son branch ve son Engine kontrol edilir.
4. Devam edilecek Engine seçilir.

## 3. Her Görev Sonunda Zorunlu Güncelleme

Codex veya geliştirici her görev sonunda şunları günceller:

- `CURRENT_STATUS.md`
- `CHANGELOG.md`
- `ROADMAP.md`
- İlgili `docs/architecture/*.md`
- Gerekirse `docs/devbook/*.md`

## 4. Kod-Doküman Senkron Kuralı

Kod değişip doküman değişmediyse görev tamamlanmış sayılmaz.

## 5. Duplicate Kod Yasağı

Yeni class oluşturmadan önce repository taranır. Aynı işi yapan class varsa mevcut class geliştirilir.

## 6. API Varsayımı Yasağı

Resmi kaynak doğrulanmadan endpoint, parameter veya SDK method yazılmaz. Emin olunmayan yerler TODO olarak dokümana işlenir.

## 7. Ağır İşlem Kuralı

Register, transfer, renew, sync, import, export, notification, billing, webhook gibi ağır işler direkt çalışmaz; queue + cron üzerinden çalışır.

## 8. Runtime Kuralı

Shared hosting uyumu korunur. RuntimeGuard, batch limit ve güvenli hata dönüşleri zorunludur.

## 9. Güvenlik Kuralı

Log, mail, queue response veya dokümana açık credential yazılmaz.

Yasak alanlar:

- api-key
- password
- passwd
- token
- auth-code
- credential
- raw request url

## 10. Teslim Raporu Standardı

Her görev sonunda şu rapor verilir:

- Engine
- Branch
- Değişen Dosyalar
- Yeni Classlar
- Güncellenen Classlar
- Yeni Methodlar
- Database Değişiklikleri
- Güncellenen Dokümanlar
- Güncellenen CURRENT_STATUS.md
- Güncellenen CHANGELOG.md
- Yeni Architecture Dosyaları
- TODO'lar
- Test Senaryoları
- Bilinen Riskler
- Performans Etkisi
- Sonraki Engine
