# Institutional accounting foundation — Finance 1.4

## Purpose
Finance 1.4 adds reusable financial-accounting workflow primitives without embedding rules from a specific association.

## Domain boundaries
- Finance owns accounts, movements, budgets, payment/collection orders, approval audit and financial status.
- Membership owns membership/fee semantics.
- Volunteers owns volunteer activity and the reason for a reimbursement.
- Organizations owns structures and appointments.
- Governance owns deliberations and institutional decisions.
- Documents owns file storage, versions, ACL and protected downloads.
- Finance stores only optional stable references to those domains.

## Order workflow
An order is a request to collect or pay a monetary amount through a Finance account. It may reference a budget line, owner, counterparty, source record and supporting evidence.

Approval steps are sequential and must be performed by distinct Joomla users. The number of required approvals is configurable per order (1–5). Finance does not hardcode organization-specific job titles.

When an expense order references a budget line, final approval verifies that planned amount minus realized transactions minus other pending/approved commitments is sufficient. Execution creates one idempotent transaction and records its id on the order.

## Audit rules
- Financial transactions are append-only through the public API.
- Deposit movements remain append-only.
- Executed orders cannot be cancelled.
- An order retains its approval records.
- External keys provide idempotency where the source can supply a stable key.
- Supporting files remain in Documents or another owning system; Finance stores references only.

## Reporting
Operational reports expose account availability, income/expense totals, category totals and budget planned/realized/committed/available figures.

This layer is not a substitute for statutory bookkeeping, tax filings, payroll, VAT registers, professional accounting advice or legally compliant digital preservation.
