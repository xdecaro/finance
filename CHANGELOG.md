# Changelog

## 1.1.0 - 2026-09-09

- Migrated optional Core consumption to the canonical `xdecaro\Core` namespace.
- Core-backed reference and shared UI features now require Core by xdecaro 1.3.0+.
- Preserved Finance standalone behavior when Core is absent or incompatible.
- Added CI regression protection against runtime use of the deprecated `Xdecaro\Core` namespace.
- Preserved `com_decarofinance`, `pkg_decarofinance`, `Xdecaro\Component\Decarofinance` and all Finance database tables.
- No schema or financial-domain behavior changes.

## 1.0.0 - 2026-09-08

- Initial Finance by xdecaro architecture.
- Administrator dashboard.
- Budgets and budget lines.
- Charges and due dates.
- Deposits with append-only ledger movements.
- Payments.
- Financial transactions.
- External source references and idempotency keys for future integrations.
- Initial Competitions integration contract documentation.
- Joomla package/build/update metadata.
