# Finance by xdecaro

Finance is the financial layer of the xdecaro Joomla ecosystem. Current version: **1.3.0**.

## Ownership
Finance owns budgets, obligations, payments, payment allocations, deposit/caution accounts and append-only movements, financial transactions, due dates and audit metadata. The source product owns *why* a charge exists. Competitions, Membership, Courses, Events or other products submit normalized financial records through Finance's public service; Finance does not copy their business rules.

## Public service
`DecarofinanceComponent::getFinanceService()` exposes operations for obligations, payments, allocations, deposit accounts/movements, transactions and budgets. External writes should provide `external_key`. Debtor/payer references use `component + entity + id`, so People, Organizations, teams and future entities remain optional.

Finance 1.3.0 adds replay-safe synchronization primitives for cross-product integrations:
- `upsertObligation()` creates or updates an external-key-backed obligation while it is still open and unallocated; unchanged replays remain valid after allocation, but conflicting financial changes are rejected;
- `upsertPayment()` behaves the same way for payments and becomes immutable once an allocation exists;
- `allocatePaymentIdempotent()` treats an identical replay as a no-op and rejects a different amount for the same payment/obligation pair.

The existing `createObligation()`, `recordPayment()` and strict `allocatePayment()` methods remain available and behaviorally compatible.

## Integrations
Core 1.4 is optional and supplies entity/relation references, UI and capability registry. Finance declares `finance.obligations`, `finance.payments`, `finance.deposits`, `finance.budgets`, `finance.query`, `finance.analytics.provider`, `finance.notifications.bridge`, `finance.tasks.bridge`.

Notifications and Tasks are optional. The Joomla Scheduled Tasks plugin can remind a configured manager about due/overdue obligations. Analytics integration is supplied by the bundled `xdecaroanalytics` plugin; Analytics reads Finance only through the public source service and Finance ACL.

## Compatibility and security
Target Joomla 4/5/6 with PHP 8.1+. Server-side ACL and CSRF are enforced for administrator writes. SQL uses `#__`, bound queries or integer-cast identifiers, non-destructive updates, and `utf8mb4` storage. Package CI performs real clean installs on Joomla 4.4.14, 5.4.8 and 6.1.3 without requiring optional xdecaro products.

Replay-safe upserts never rewrite allocated or closed financial data. This keeps retries safe without allowing a source product to silently alter accounting history after money has been allocated.

## Build
`bash build/build.sh` creates component, Analytics plugin, Scheduler plugin, package and `SHA256SUMS.txt` in `dist/`.
