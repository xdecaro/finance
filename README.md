# Finance by xdecaro

Finance is the financial layer of the xdecaro Joomla ecosystem. Current version: **1.5.3**.

## Ownership
Finance owns budgets, obligations, payments, payment allocations, deposit/caution accounts and append-only movements, financial accounts, financial transactions, internal transfers, cash checks, collection/payment orders, statement snapshots, due dates, budget coverage and financial audit metadata.

The source product owns *why* a charge, reimbursement, contribution or payment exists. Membership, Competitions, Courses, Events, Volunteers and other products submit normalized financial records through Finance's public service. Finance does not copy their business rules and does not require those products to be installed.

## Public service
`DecarofinanceComponent::getFinanceService()` exposes operations for:
- obligations, payments and allocations;
- deposit/caution accounts and append-only movements;
- financial accounts and append-only financial transactions;
- budgets and budget lines;
- collection/payment orders, multi-step approvals and execution;
- internal account transfers;
- cash reconciliation checks;
- statement snapshots, lines, finalisation and approval.

External writes should provide `external_key` whenever a stable source key exists. Entity links use `component + entity + id`, so People, Organizations, Documents and other products remain optional.

Replay-safe synchronization remains available through `upsertObligation()`, `upsertPayment()` and `allocatePaymentIdempotent()`. Allocated or closed financial history cannot be silently rewritten.

## Institutional accounting workflow
Finance 1.4.0 introduced generic accounting workflow foundations:
1. create a financial account (bank, cash, payment or other);
2. create budgets and categorized lines for an owning structure;
3. register direct financial transactions, optionally linked to source records, counterparties and supporting evidence;
4. create a collection/payment order;
5. collect the configured number of approval steps from distinct Joomla users;
6. for expense orders linked to a budget line, verify remaining coverage before final approval;
7. execute the approved order exactly once into a financial transaction;
8. report planned, realized, committed and available amounts.

The workflow is generic. Role names such as President, Treasurer or Senior Councillor are not hardcoded into Finance; Organizations/Governance or installation policy may determine who receives the Joomla ACL permissions and which approval roles are used.

## Reconciliation and statements
Finance 1.5.0 adds three controls above the transaction ledger:
- **internal transfers** generate two linked immutable movements, one outgoing and one incoming, and are excluded from operating income/expense KPIs;
- **cash checks** snapshot the ledger balance at the check time, compare it with the physical count and retain the variance without silently changing the ledger;
- **statements** provide typed, ordered snapshot containers with an immutable transition from draft to finalised and then approved. The finaliser and approver must be different Joomla users.

Statement types include operational, financial-position, management, mission, social and custom records. These names classify the workflow and document snapshot; they do not assert compliance with any statutory accounting format.

## Reporting boundary
The Reporting section provides operational totals, account availability, category summaries, budget coverage and reconciliation indicators. Finance 1.5.0 does **not** claim to generate a legally complete statutory balance sheet, tax return, payroll accounting, VAT ledger or legally compliant digital preservation. Those requirements should be implemented only against verified accounting/legal specifications and, where appropriate, specialist external services.

## Integrations
Core 1.4 is optional and supplies entity/relation references, shared UI and capability registry. Finance declares:
`finance.obligations`, `finance.payments`, `finance.deposits`, `finance.budgets`, `finance.accounts`, `finance.transactions`, `finance.transfers`, `finance.cashchecks`, `finance.orders`, `finance.statements`, `finance.reporting`, `finance.query`, `finance.analytics.provider`, `finance.notifications.bridge`, `finance.tasks.bridge`.

Documents can be referenced as supporting evidence without becoming a required dependency. Notifications and Tasks remain optional. Analytics integration is supplied by the bundled `xdecaroanalytics` plugin.

## Compatibility and security
Target Joomla 4, 5 and 6 where technically possible, with PHP 8.1+. Administrator writes enforce server-side ACL and CSRF. SQL uses `#__`, bound queries or integer-cast identifiers, non-destructive updates and `utf8mb4` storage. Financial transaction and deposit ledgers are append-only through the public service: corrections should be represented by explicit new movements rather than destructive rewrites.

The package CI performs clean installation and runtime regression checks on supported Joomla branches without requiring optional xdecaro products. Administrator code resolves Finance services through the booted component API rather than Joomla's global DI container, preserving the component-local service-provider boundary. Finance administrator styling is attached through a centralized scoped head link to `/media/com_decarofinance/css/admin.css`, matching the path verified to load correctly on the target Joomla administrator.

## Build
`bash build/build.sh` creates the component, Analytics plugin, Scheduler plugin, package and `SHA256SUMS.txt` in `dist/`.
