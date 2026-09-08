# Finance architecture

## Boundaries

Finance stores financial facts and obligations. Source products store the reason those facts exist.

Examples:

- Competitions creates a disciplinary sanction; Finance stores the monetary charge.
- Membership decides a membership fee; Finance stores the charge/payment.
- Courses decides an enrolment fee; Finance stores the charge/payment.

## Core entities

### Budget
A planning period, normally a fiscal year.

### Budget line
A planned income or expense line. Actual values should be derived from realized transactions instead of being manually duplicated.

### Charge
An amount owed. It may be due, partially paid, paid, contested or cancelled.

### Payment
An actual incoming or outgoing payment.

### Deposit
A caution/deposit associated with an external entity, such as a team in Competitions.

### Deposit movement
Append-only credit/debit/refund/adjustment entry. The current deposit balance is the signed sum of the ledger.

### Transaction
A realized financial movement used for reporting and budget actuals.

## External references

External records use:

- `source_component`;
- `source_entity_type`;
- `source_entity_id`;
- optional `source_context_type`;
- optional `source_context_id`;
- `external_key` when an idempotent external write is possible.

No foreign key points into another Joomla extension. This keeps Finance independently installable.
