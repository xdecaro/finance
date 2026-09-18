# Finance 1.5 — reconciliation and statement controls

## Internal account transfers
A transfer moves one amount between two active Finance accounts using the same currency. Finance stores a transfer record plus two linked append-only transactions:
- expense on the source account;
- income on the destination account.

The pair changes individual account balances but not the organization's operating income/expense KPIs. Stable external keys make retries idempotent.

## Cash checks
Cash checks are allowed only on accounts whose type is `cash`. At the chosen check timestamp Finance calculates the expected ledger balance and stores:
- expected balance;
- physically counted balance;
- difference;
- note;
- optional evidence reference.

A variance does not automatically create an adjustment transaction. Any correction must be an explicit, auditable financial movement.

## Statement snapshots
Statements are generic financial/document snapshots. Supported workflow classifications are:
- operational;
- financial position;
- management;
- mission;
- social;
- custom.

A statement starts as `draft`. Lines may be added only while it is a draft. Finalisation requires at least one line and locks further line creation. Approval is allowed only after finalisation and must be performed by a different Joomla user from the finaliser.

Statement lines may contain an amount, subtotal, text or note and can carry optional source references. A statement itself can reference an optional Documents record without requiring Documents to be installed.

## Domain and compliance boundary
Finance manages workflow, references, audit metadata and operational reporting. It does not validate whether a statement satisfies a specific statutory, tax, accounting-principles or digital-preservation requirement. Any legally prescribed output must be implemented against the applicable verified specification and professional/accounting requirements.
