# Finance by xdecaro

Finance by xdecaro is the financial management component for the xdecaro Joomla ecosystem.

Current version: **1.1.0**.

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

## Core by xdecaro

Core is optional for Finance. Finance `1.1.0` consumes public Core APIs only through the canonical `xdecaro\Core` namespace and only when Core `1.3.0+` is available.

Core may provide shared UI assets and cross-product entity/relation references. When Core is absent, older than `1.3.0`, or incompatible, Finance remains usable with its local UI and domain logic. Finance never moves budgets, obligations, payments, deposits or ledger rules into Core.

The deprecated `Xdecaro\Core` compatibility namespace is not consumed by Finance runtime code.

## Build

Run:

```bash
bash build/build.sh
```

This generates:

- `dist/com_decarofinance_1.1.0.zip`
- `dist/pkg_decarofinance_1.1.0.zip`
- `dist/SHA256SUMS.txt`
