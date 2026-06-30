# Changelog

## 2026-06-30 - Task 09 V1 Install & Runtime Fix

Branch: `codex/task-09-v1-install-runtime-fix`

Base: `codex/v1-recovery-task04-05-admin-ux`

### Fixed

- Removed explicit `DEFAULT NULL` from `TEXT` and `MEDIUMTEXT` SQL definitions for cleaner MySQL/MariaDB compatibility during module install.
- Updated runtime schema repair definitions to use `TEXT NULL` and `MEDIUMTEXT NULL`.

### Verified

- Hook registration and hook method coverage for the V1 runtime hooks.
- Static module `require_once` paths.
- Duplicate PHP class declarations.
- ZIP root and path format during package generation.

### Notes

- No new feature, provider endpoint, engine, or dashboard was added.
- PHP lint and live PrestaShop runtime checks were not available in this workspace.
