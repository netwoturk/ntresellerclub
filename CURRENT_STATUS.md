# Current Status

Tarih: 2026-06-25

## Son Engine

Engine 09 - Notification & Mail Engine

## Son Branch

`codex/engine-09-notification-mail`

## Tamamlananlar

- Merkezi notification backend altyapısı eklendi.
- `NtRcNotificationEngine`, `NtRcMailTemplateManager` ve `NtRcNotificationQueueManager` class'ları oluşturuldu.
- Notification template, queue ve log tabloları installer schema guard ile `install.sql` içine eklendi.
- Template sistemi TR, EN, DE, FR, ES, IT dilleri için backend seed akışıyla hazırlandı.
- Domain, hosting, SSL, renewal, ödeme, suspension/expiry, queue failed ve provider down template key listesi tanımlandı.
- Mail gönderimi doğrudan ağır işlem içinde yapılmayacak şekilde `ntresellerclub_notification_queue` üzerinden batch işleme bağlandı.
- PrestaShop `Mail::Send` uyumlu `notification.html` ve `notification.txt` wrapper şablonları tüm desteklenen diller için eklendi.
- Customer, admin ve technical_admin alıcı tipleri backend queue seviyesinde ayrıldı.
- Notification queue status akışı `pending`, `processing`, `sent`, `failed`, `cancelled` olarak tanımlandı.
- Retry alanları `retry_count`, `max_retries`, `last_error` ile eklendi.
- Cron sonunda Monitoring Engine çıktısından sonra Notification Engine çalışacak şekilde entegrasyon yapıldı.
- Failed operation queue ve provider health DOWN/WARNING/ERROR durumları admin notification üretebilir hale getirildi.
- Domain expiry için 30/15/7/1 gün kala customer notification altyapısı hazırlandı.
- Mail subject/body/variables, retry error ve notification log mesajları credential-like değerleri maskeleyecek şekilde sanitize edildi.
- Notification & Mail architecture dokümanı eklendi.
- `DATABASE_SCHEMA.md`, `API_CONTRACT_RULES.md`, `ROADMAP.md` ve `CHANGELOG.md` Engine 09 kapsamıyla güncellendi.

## Devam Edenler

- Admin template/panel bilinçli olarak eklenmedi; bu engine backend, manager, tablo ve dokümantasyon kapsamındadır.
- Hosting, SSL, payment, suspension ve service expired event'lerinin gerçek workflow hook'ları ileriki engine'lerde `NtRcNotificationEngine::enqueueServiceNotification()` üzerinden bağlanacak.
- `NTRC_TECHNICAL_ADMIN_EMAIL` için admin ayar ekranı yok; değer yoksa `PS_SHOP_EMAIL` fallback kullanılır.
- Queue retention/cleanup politikası ileriki admin dashboard veya maintenance engine kapsamında netleştirilecek.

## Sıradaki Engine

Öneri: Engine 10 - Renewal / Service Lifecycle Notification Wiring. Notification altyapısı hazır olduğu için renewal, suspension, expiry ve payment-required olaylarının gerçek servis yaşam döngüsüne bağlanması mantıklı sonraki adımdır.

## Bilinen Riskler

- PHP CLI bu çalışma ortamında bulunmadığı için `php -l` lint çalıştırılamadı.
- `Mail::Send` gerçek SMTP/PrestaShop runtime içinde test edilmedi; branch üzerinde statik entegrasyon kontrolü yapıldı.
- Notification queue batch işlemi shared hosting uyumu için limitlidir; çok yüksek backlog için birden fazla cron turu gerekir.
- Technical admin e-postası config fallback ile çalışır; ayrı admin UI olmadığı için özel adres yönetimi henüz yoktur.

## Son Test

- GitHub raw dosyaları üzerinden yeni class, cron, installer, SQL ve doküman dosyalarının branch'te varlığı doğrulandı.
- Yeni PHP dosyalarında kaba `{}` ve `()` dengesi kontrol edildi.
- `Mail::Send`, `NtRcRuntimeGuard::beforeHeavyProcess`, `cronBatchLimit`, notification status listesi, recipient type listesi ve template key listesi statik olarak doğrulandı.
- `install.sql` ve `NtRcInstaller.php` içinde `ntresellerclub_notification_template`, `ntresellerclub_notification_queue`, `ntresellerclub_notification_log` tabloları doğrulandı.
- TR, EN, DE, FR, ES, IT için `notification.html` ve `notification.txt` mail wrapper dosyaları doğrulandı.
- Credential-like değerlerin mail/log/retry error içine düz yazılmasını engelleyen sanitize helper'ları kontrol edildi.

## Son Commit

Bu `CURRENT_STATUS.md` kaydı Engine 09 final durumunu temsil eder.

## Son Doküman Güncellemesi

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/architecture/12_NOTIFICATION_MAIL_ENGINE.md`
- `prestashop/ntresellerclub/docs/API_CONTRACT_RULES.md`
- `prestashop/ntresellerclub/docs/DATABASE_SCHEMA.md`
