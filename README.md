# Finance by xdecaro

Finance by xdecaro is the financial management component for the xdecaro Joomla ecosystem.

Initial version: **1.0.0**.

## Technical identifiers

- Joomla component: `com_decarofinance`
- Joomla package: `pkg_decarofinance`
- Namespace: `Xdecaro\Component\Decarofinance`
- Repository: `xdecaro/Finance`

## Scope

Finance owns budgets, budget lines, charges, payments, deposits, deposit ledger movements and financial transactions.

Other xdecaro products keep their own business rules. For example, Competitions decides that a team owes a participation fee, a deposit or a disciplinary fine; Finance manages the resulting financial obligation, payment, deposit balance and audit trail.

## Competitions target integration

The data model supports:

- participation fees;
- deposits/cautions;
- yellow-card, red-card, suspension, fight/misconduct, no-show and withdrawal fines;
- deposit deductions;
- deposit top-ups;
- deposit refunds;
- external idempotency keys to prevent duplicate charges.

## Compatibility

Target Joomla 4, 5 and 6 where technically possible. The component uses Joomla MVC, Form API, DatabaseInterface, ACL, CSRF protection, language files and Web Asset Manager.

## Xdecaro Core

Finance may opt in to public Xdecaro Core UI assets when Core is installed. Finance business logic remains in Finance.

## Build

Run:

```bash
bash build/build.sh
```

This generates:

- `dist/com_decarofinance_1.0.0.zip`
- `dist/pkg_decarofinance_1.0.0.zip`
- `dist/SHA256SUMS.txt`
