# Current Status

Tarih: 2026-06-25

## Son Engine

Engine 10 - Renewal / Service Lifecycle Notification Wiring

## Son Branch

`codex/engine-10-renewal-service-lifecycle-notification`

## Tamamlananlar

- Engine 09 notification altyapısı gerçek renewal ve servis lifecycle olaylarına bağlandı.
- `NtRcRenewalManager` doğrudan `Mail::Send` / `renewal_reminder` kullanmayacak şekilde güncellendi.
- Renewal scan artık 30/15/7/1 gün domain expiry event'lerini `NtRcNotificationEngine::enqueueExpiryNotification()` üzerinden notification queue'ya yazar.
- `NtRcNotificationEngine::enqueueExpiryNotification()` merkezi expiry helper olarak eklendi.
- Notification expiry taraması `domain` ve `tr_domain` servislerini destekleyecek şekilde merkezi helper'a taşındı.
- Başarılı domain `register`, `transfer`, `renew` queue işlemlerinden sonra müşteri notification event'i oluşturuldu.
- Domain lifecycle template mapping eklendi: `domain_registered`, `domain_transfer_started`, `domain_renewed`.
- `NtRcServiceRepository::updateStatus()` status değişimi `suspended` veya `expired` olduğunda customer notification queue oluşturur hale getirildi.
- Notification enqueue başarısızlığı provider queue başarısını geri almayacak şekilde best-effort / warning-log yaklaşımıyla bağlandı.
- Provider response sanitization operation queue tarafında token/credential benzeri alanları da kaldıracak şekilde genişletildi.
- `docs/architecture/13_RENEWAL_SERVICE_LIFECYCLE_NOTIFICATION_WIRING.md` eklendi.
- `ROADMAP.md`, `CHANGELOG.md`, `DATABASE_SCHEMA.md`, `API_CONTRACT_RULES.md` Engine 10 kapsamıyla güncellendi.

## Database Değişikliği

- Yeni tablo eklenmedi.
- Engine 10 mevcut `ntresellerclub_notification_template`, `ntresellerclub_notification_queue`, `ntresellerclub_notification_log` tablolarını kullanır.
- Eski `ntresellerclub_notice` tablosu geriye uyumluluk için bırakıldı; Engine 10 yeni renewal notice kaydı yazmaz.

## Devam Edenler

- `payment_required` event'i henüz gerçek billing/payment workflow'una bağlanmadı.
- Hosting/SSL created/renewed notification event'leri, ilgili provisioning engine'leri tamamlandığında bağlanacak.
- Admin notification template UI hâlâ yok; backend template seed ve queue altyapısı kullanılıyor.

## Sıradaki Engine

Öneri: Engine 06 - Hosting Provisioning veya Engine 11 - Billing / Payment Required Wiring. Notification altyapısı hazır olduğu için hosting provisioning ve billing event bağlantısı iki doğal sonraki yoldur.

## Bilinen Riskler

- PHP CLI bu çalışma ortamında bulunmadığı için `php -l` lint çalıştırılamadı.
- Gerçek PrestaShop runtime, SMTP ve provider sandbox çalıştırması bu ortamda yapılamadı.
- Notification enqueue best-effort çalışır; mail kuyruğu yazılamazsa provider operasyonu başarılı kalır ve warning log üretilir.
- Raw GitHub CDN kısa süre eski blob döndürebilir; doğrulamada GitHub contents API ve branch head esas alındı.

## Son Test

- GitHub branch farkı `codex/engine-09-notification-mail...codex/engine-10-renewal-service-lifecycle-notification` üzerinden doğrulandı.
- Yeni/değişen PHP dosyalarında kaba `{}` ve `()` dengesi kontrol edildi.
- `NtRcRenewalManager` içinde `Mail::Send`, `renewal_reminder` ve `ntresellerclub_notice` kullanımının kaldırıldığı doğrulandı.
- `NtRcNotificationEngine::enqueueExpiryNotification()` ve lifecycle template key mapping satırları doğrulandı.
- `NtRcOperationQueueProcessor` içinde `domain_registered`, `domain_transfer_started`, `domain_renewed` mapping'i doğrulandı.
- `NtRcServiceRepository` içinde `service_suspended`, `service_expired` status mapping'i doğrulandı.
- `NtRcNotificationQueueManager` dışında doğrudan `Mail::Send` kalmadığı kontrol edildi.
- Credential-like değerleri maskeleyen safeText/sanitize alanları kontrol edildi.

## Son Commit

Bu `CURRENT_STATUS.md` kaydı Engine 10 final durumunu temsil eder.

## Son Doküman Güncellemesi

- `CHANGELOG.md`
- `CURRENT_STATUS.md`
- `ROADMAP.md`
- `docs/architecture/13_RENEWAL_SERVICE_LIFECYCLE_NOTIFICATION_WIRING.md`
- `prestashop/ntresellerclub/docs/API_CONTRACT_RULES.md`
- `prestashop/ntresellerclub/docs/DATABASE_SCHEMA.md`
