# Changelog

## 1.5.0 — 2026-09-18
- Added idempotent transfers between financial accounts, implemented as paired append-only expense/income transactions so account balances remain auditable without inflating operating income/expense KPIs.
- Added account codes for clearer bank/cash ledger identification.
- Added physical cash checks with expected balance snapshot, actual counted balance, difference, notes and optional supporting-document reference.
- Added generic financial statements with typed statement records, ordered lines, optional source/document references, finalisation lock and separate approval by a different Joomla user.
- Added administrator views for Transfers, Cash checks and Statements, plus new dashboard/reporting KPIs and responsive dark-safe layouts.
- Added the dedicated `finance.reconcile` ACL action for cash checks and statement finalisation while retaining separate approval and execution permissions.
- Extended Core capabilities and Analytics datasets/metrics for transfers, cash checks and statements.
- Added non-destructive 1.5.0 SQL updates, Joomla runtime coverage and real 1.4.0 → 1.5.0 upgrade preservation tests.
- Statement types such as financial position, management, mission and social report are workflow/snapshot containers only; Finance does not claim statutory, tax or legal-compliance completeness.

## 1.4.0 — 2026-09-18
- Added financial accounts for bank, cash, payment and other ledgers, with optional organization ownership and opening balances.
- Added append-only financial transactions linked to accounts, budget lines, categories, source records, counterparties and optional document/evidence references.
- Added generic collection/payment orders with configurable multi-step approval, distinct approvers, audit metadata and execution into immutable transactions.
- Added budget ownership, currency, line codes/categories and live planned/realized/committed/available coverage calculations.
- Added server-side budget coverage validation before final approval of expense orders linked to a budget line.
- Added administrator sections for Accounts, Transactions, Orders and Reporting, plus dashboard KPIs and responsive/dark-safe UI refinements.
- Added separate ACL actions for approving and executing financial orders.
- Extended Core capabilities and Analytics datasets/metrics for accounts, transactions, orders and budget usage.
- Added non-destructive SQL upgrades and preserved all 1.3.0 public APIs and existing data.
- This release provides institutional accounting workflow foundations; it does not claim to replace statutory bookkeeping, tax software or legally compliant digital preservation.

## 1.3.0 — 2026-09-09
- Added `upsertObligation()` for external-key-backed obligation synchronization before allocation or closure.
- Added `upsertPayment()` for external-key-backed payment synchronization before allocation.
- Added `allocatePaymentIdempotent()` so cross-product retries can safely replay the same payment allocation while conflicting amounts remain errors.
- Preserved existing `createObligation()`, `recordPayment()` and `allocatePayment()` behavior for current consumers.
- Prevented changed obligation/payment data from rewriting financial history after allocations exist.
- Added real Joomla 4/5/6 runtime coverage for update-before-allocation, replay-after-allocation and conflict rejection.
- No schema changes.

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
