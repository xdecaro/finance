# Finance — Repository Guidelines

## Product scope

Finance by xdecaro is the financial layer of the xdecaro Joomla ecosystem. Keep financial-domain logic here and domain-specific triggers in the owning product.

Finance owns budgets, charges, payments, deposits, transaction records, due dates, allocations, financial status and audit metadata.

Competitions owns competitions, teams, matches, cards, suspensions, fights and disciplinary rules. Membership owns members and memberships. Courses owns courses and enrolments. Events and Bookings own their own booking semantics.

Never move those product rules into Finance. Finance should receive a normalized financial obligation or movement with a stable external reference.

## Core integration

Use Xdecaro Core only for genuinely shared, domain-neutral infrastructure and only after verifying the public API actually exists. Do not invent Core APIs.

Core may provide shared UI assets, design tokens, diagnostics and integration reference objects. Finance remains responsible for financial persistence and business rules. Core must never depend on Finance.

## External integration and idempotency

Every external write must support an `external_key` when the caller can provide one. Treat it as an idempotency key. A retry from Competitions or another product must not create a duplicate charge, payment or deposit movement.

Store the source component, source entity type/id and optional source context type/id. Never require another xdecaro extension to be installed for Finance to work.

## Deposits

A deposit/caution is not just a mutable balance. Preserve an append-only deposit movement ledger. Credits, debits, refunds and adjustments must remain auditable.

Do not silently delete ledger movements to change the balance. Correct mistakes with an explicit reversing or adjustment entry.

## Budgets

Separate planned values from realized financial transactions. Budget lines describe planned income/expense; actuals should derive from transactions whenever possible.

## Joomla and security

Target Joomla 4, 5 and 6 where technically possible. Use modern Joomla APIs, namespace, MVC, service provider, DatabaseInterface, Form API, Language, ACL and Web Asset Manager.

Enforce ACL server-side, validate CSRF tokens for state-changing actions, filter and validate input, escape output, use bound database queries and never authorize only in JavaScript.

Use `#__` for every table. Updates must preserve data and configuration.

## UI/UX

Keep administrator UI consistent with other xdecaro products. Prefer Core shared UI assets when available, with local fallbacks. Verify desktop, tablet, smartphone, light mode and dark mode. Avoid hardcoded light-only colors.

## Versions and releases

Use Semantic Versioning. Keep `VERSION`, component manifest, package manifest, update feed, changelog and release ZIP names aligned. Never publish different file contents under the same version.

ZIPs must be directly installable in Joomla and must not include backups, temporary files, IDE files or nested unnecessary ZIPs.

## Working rule

When the user says `procedi`, execute directly after inspecting relevant code and dependencies. Preserve working behavior and avoid unrelated refactors.
