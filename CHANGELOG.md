# Changelog

## 1.5.5 — 2026-09-19
- Fixed a financial-integrity bug found during administrator testing: when a payment was created and its obligation allocation then failed, the payment row remained stored with zero allocation.
- Added `recordPaymentAndAllocate()`, which records and allocates a payment in one database transaction.
- Administrator payment forms now use the atomic path whenever an obligation is selected; if allocation fails, both the allocation and the new payment are rolled back.
- Unallocated standalone payments remain supported when no obligation is selected.
- Added real Joomla runtime coverage proving that an overpayment rejection does not leave an orphan payment record.
- No database schema changes; the new service method is additive and backwards compatible.

## 1.5.4 — 2026-09-19
- Fixed administrator monetary inputs using locale decimal commas, including the Budget line error `Invalid planned_amount.` for values such as `500,00`.
- Finance now normalizes common monetary formats safely before validation: `500`, `500.00`, `500,00`, `1.234,56` and `1,234.56`.
- The normalization is centralized in the Finance service, so the same behavior applies consistently to budgets, accounts, obligations, payments, orders, transfers, transactions, cash checks and other monetary writes.
- Ambiguous or malformed monetary strings remain rejected instead of being guessed silently.
- Added real Joomla runtime coverage for comma-decimal and grouped monetary input on supported Joomla versions.
- No database schema or public API changes.

## 1.5.3 — 2026-09-18
- Fixed the remaining Finance administrator styling issue confirmed by browser-console diagnostics: the stylesheet file returned HTTP 200 and applied correctly when injected directly, while no Finance stylesheet link was present in the document head.
- Finance administrator views now attach the verified `/media/com_decarofinance/css/admin.css` URL as a scoped stylesheet head link through the centralized `UiHelper`.
- Removed the critical runtime dependency on Finance Web Asset Manager registration for the local administrator stylesheet while keeping `joomla.asset.json` packaged as metadata.
- Added regression coverage for the direct stylesheet contract. No database schema or public API changes.

## 1.5.2 — 2026-09-18
- Fixed missing Finance administrator styling on Joomla by loading the component stylesheet through an explicit runtime Web Asset Manager registration.
- Added a centralized `UiHelper::loadAssets()` used by every Finance administrator view, avoiding reliance on extension asset-registry discovery for the critical admin stylesheet.
- Kept `joomla.asset.json` as packaged asset metadata while making the visible administrator UI resilient if registry discovery does not activate the stylesheet.
- Added regression checks that every administrator view loads the centralized asset helper and that the installed CSS/helper files are present after a real Joomla installation.
- Added a real Joomla 1.5.1 → 1.5.2 upgrade test; no database schema or public API changes.

## 1.5.1 — 2026-09-18
- Fixed the administrator fatal error `Resource '...FinanceQueryService' has not been registered with the container`.
- Administrator views now resolve Finance query/Core services from the booted `com_decarofinance` component, which owns the extension service container, instead of requesting component-local services from Joomla's global container.
- Finance write actions now resolve `FinanceService` through the same component API, preventing the equivalent failure when submitting forms.
- Added a regression contract that rejects direct global-container lookups for component-owned services.
- Added a real Joomla upgrade test from released Finance 1.5.0 to 1.5.1; no database schema or public Finance API changes.

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
