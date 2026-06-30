# Changelog

## 2026-06-30 - Task 08 V1 Domain Flow Recovery

Branch: `codex/v1-recovery-task04-05-admin-ux`

### Added

- Recovered the customer domain search page, template, JavaScript, and CSS.
- Added `/module/ntresellerclub/myservices` alias for the customer domain services screen.
- Added read-only Admin Domains and Admin TR Domains service/queue visibility.
- Added `actionOrderStatusPostUpdate` support for payment accepted after order creation.
- Added module upgrade script `upgrade/install-0.1.1.php`.
- Added runtime smoke test checklist documentation.

### Changed

- Module version is now `0.1.1`.
- Domain cart errors now expose user-friendly codes: `product_mapping_missing`, `unavailable`, `duplicate`, and `failed`.
- Cart domain metadata is normalized before order orchestration.
- Order orchestration validates cart metadata before service and queue creation.
- Customer services page now shows only the current customer's domain and TR domain services with latest queue status.

### Security

- Hooks do not call provider APIs directly.
- Register, transfer, and renew provider calls are not added to frontend controllers.
- Customer pages do not expose raw provider errors or credentials.
- Upgrade/default repair does not overwrite stored credentials or product mapping values.

## Previous Work

Task 01 through Task 06 history exists on the corresponding task branches. This recovery branch is based on `codex/task-06-simple-admin-ux-redesign-v1` and restores the lost Task 04/05 V1 domain flow pieces on top of that base.
