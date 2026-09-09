# Changelog

## 1.2.0 — 2026-09-09
- Added public transactional/idempotent Finance service.
- Added typed debtor and payer references without hard dependencies on People/Organizations.
- Added payment allocation validation and automatic obligation status updates.
- Added append-only deposit account/movement APIs and budgets administration.
- Added administrator views for obligations, payments, deposits and budgets.
- Added Core 1.4 capability registration.
- Added optional Notifications and Tasks reminders through Joomla Scheduled Tasks.
- Added optional Analytics provider.
- Fixed Joomla SQL manifest charset declaration while retaining utf8mb4 tables.
- Added deterministic plugin/package builds and Joomla 4/5/6 runtime CI.

## 1.1.0 — 2026-09-08
- Added optional Core integration through canonical `xdecaro\\Core` namespace.

## 1.0.0 — 2026-09-08
- Initial financial data model and package.
