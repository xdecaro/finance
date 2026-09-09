# Changelog

## 1.2.1 — 2026-09-09
- Fixed Joomla `DatabaseInterface::insertObject()` / `updateObject()` calls that passed temporary objects where the driver requires reference-safe variables.
- Covered payment allocations, obligation status updates, deposit account creation, budgets and budget lines.
- Preserved all public Finance APIs, idempotency contracts, schema and data.
- Added regression coverage for deposit ledger, payment allocation and budget writes on real Joomla runtimes.

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
