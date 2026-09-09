# Finance by xdecaro

Finance is the financial layer of the xdecaro Joomla ecosystem. Current version: **1.2.1**.

## Ownership
Finance owns budgets, obligations, payments, payment allocations, deposit/caution accounts and append-only movements, financial transactions, due dates and audit metadata. The source product owns *why* a charge exists. Competitions, Membership, Courses, Events or other products submit normalized financial records through Finance's public service; Finance does not copy their business rules.

## Public service
`DecarofinanceComponent::getFinanceService()` exposes idempotent operations for obligations, payments, allocations, deposit accounts/movements, transactions and budgets. External writes should provide `external_key`. Debtor/payer references use `component + entity + id`, so People, Organizations, teams and future entities remain optional.

## Integrations
Core 1.4 is optional and supplies entity/relation references, UI and capability registry. Finance declares `finance.obligations`, `finance.payments`, `finance.deposits`, `finance.budgets`, `finance.query`, `finance.analytics.provider`, `finance.notifications.bridge`, `finance.tasks.bridge`.

Notifications and Tasks are optional. The Joomla Scheduled Tasks plugin can remind a configured manager about due/overdue obligations. Analytics integration is supplied by the bundled `xdecaroanalytics` plugin; Analytics reads Finance only through the public source service and Finance ACL.

## Compatibility and security
Target Joomla 4/5/6 with PHP 8.1+. Server-side ACL and CSRF are enforced for administrator writes. SQL uses `#__`, bound queries or integer-cast identifiers, non-destructive updates, and `utf8mb4` storage. Package CI performs real clean installs on Joomla 4.4.14, 5.4.8 and 6.1.3 without requiring optional xdecaro products.

Finance 1.2.1 is a runtime compatibility PATCH: database object writes now always pass named objects to Joomla `DatabaseInterface`, including deposit account creation, payment allocations and budget writes. There are no schema or public API changes.

## Build
`bash build/build.sh` creates component, Analytics plugin, Scheduler plugin, package and `SHA256SUMS.txt` in `dist/`.
