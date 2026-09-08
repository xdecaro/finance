# Competitions ↔ Finance integration

## Ownership rule

Competitions decides sport and discipline rules. Finance never decides whether a yellow card, red card, suspension, fight, no-show or withdrawal deserves a fine.

When Competitions determines that money is owed, it sends normalized financial data to Finance.

## Recommended mappings

| Competitions event | Finance record | Suggested charge type |
| --- | --- | --- |
| Team registration | Charge | `participation_fee` |
| Deposit required | Deposit | n/a |
| Yellow card fine | Charge | `yellow_card` |
| Double yellow fine | Charge | `double_yellow` |
| Red card fine | Charge | `red_card` |
| Suspension fine | Charge | `suspension` |
| Fight/misconduct fine | Charge | `misconduct` |
| No-show fine | Charge | `no_show` |
| Withdrawal fine | Charge | `withdrawal` |

## Idempotency

A source action must produce a stable key, for example:

`com_decarodcl:disciplinary-sanction:1842`

Finance stores the key in `external_key`. Repeating the same request must return/use the existing record rather than create a duplicate fine.

## Deposit workflow

1. Competitions determines the required caution amount.
2. Finance creates a deposit record for the team/season or team/competition.
3. Receipt of the caution creates a deposit credit ledger entry.
4. A fine may create a charge.
5. If configured by the future integration layer, that charge may create a deposit debit entry.
6. If the balance is below `min_balance`, Finance can mark the deposit as `low`.
7. A top-up creates a new credit entry.
8. End-of-season refund creates a refund entry; historical ledger stays intact.

## Important

Version 1.0.0 prepares the Finance data model and administrator management. Automatic event dispatch/listening inside Competitions should be implemented separately in the Competitions project after its current disciplinary data model and event lifecycle are inspected.
