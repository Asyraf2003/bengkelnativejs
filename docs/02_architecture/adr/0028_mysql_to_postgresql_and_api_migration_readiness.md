# ADR 0028 - MySQL to PostgreSQL and API Migration Readiness

## Status

Accepted as migration-readiness constraint.

## Context

HyperPOS currently runs on MySQL through Laravel.

The long-term direction may move the system toward PostgreSQL and a Go API.

This is not an active PostgreSQL implementation.

This is not an active Go API implementation.

The current goal is to keep new MySQL schema and application contracts disciplined so future migration does not become avoidable technical debt.

Refund, edit, revision, settlement, surplus disposition, audit, and reporting are finance-sensitive areas.

If these areas are implemented with weak date semantics, loose JSON truth, MySQL-specific shortcuts, or UI-driven decisions, future PostgreSQL and API migration will be expensive and risky.

## Problem

A MySQL-first implementation can accidentally create migration traps:

- MySQL enum status values that must later be rewritten
- unsigned integer assumptions that do not map cleanly to PostgreSQL
- timestamps used as business dates
- business dates used as audit timestamps
- JSON payloads used as primary financial truth
- missing source ids
- missing actor and reason
- cascade deletes on financial history
- UI-specific calculations
- report queries tied to current projection only
- no stable application use case contract for future API

These traps must be prevented now.

## Decision

New finance-sensitive schema and application contracts must be designed as MySQL-compatible and PostgreSQL-ready.

The system stays on MySQL for now.

PostgreSQL implementation is out of scope until a separate migration project is opened.

Go API implementation is out of scope until stable application use cases exist.

The current MySQL migrations must follow portability rules.

## Portability Rules

### Identity

Use string ids for domain identities unless a specific ADR decides otherwise.

Do not depend on auto-increment ids as public API identity.

Internal numeric ids may exist only if they are not exposed as stable public contract.

### Money

Store rupiah as integer.

Do not use float or decimal for rupiah totals.

Use signed integer only when negative values are intentionally part of the domain.

Use non-negative validation in domain or application when negative values are invalid.

Do not rely on MySQL unsigned behavior as the only guard.

### Status

Do not use MySQL enum for domain status.

Use string status columns.

Validate status in domain, DTO, application service, or adapter mapping.

Document allowed values in ADR or domain contract.

### Dates and Times

Every finance-sensitive table must clearly separate date semantics.

transaction_date means business transaction date.

effective_date means business effect date.

occurred_at means when domain action happened.

created_at means when the row was created.

updated_at means when the row was changed.

Do not infer business date from created_at.

Do not infer audit timestamp from transaction_date.

Use explicit occurred_at for actions.

Use explicit transaction_date or effective_date for business effect when needed.

### JSON

JSON may be used for snapshots, metadata, or compatibility payloads.

JSON must not be the only source of truth for:

- money
- lifecycle status
- source ids
- actor ids
- business date
- occurred_at
- inventory quantity
- payment/refund amount
- customer balance amount
- report-critical fields

If JSON is used for snapshots, important queryable facts must also exist as explicit columns or derived indexed projections.

### Foreign Keys and Deletion

Use foreign keys where practical.

Use restrict-on-delete for financial history.

Do not cascade delete financial history.

Do not use nullable foreign keys as a shortcut for missing immutable snapshots.

If legacy compatibility requires nullable source ids, document the legacy uncertainty and test it.

### Indexes

Indexes must follow real read paths.

Do not index randomly.

For audit and timeline reads, index by aggregate, occurred_at, event name, and actor where relevant.

For financial ledgers, index source id, note root id, status, and occurred_at where relevant.

For UI/API target under one second, use projection or indexed read models when direct ledger reads become heavy.

### Application Boundary

Controllers are transport adapters.

Blade UI is not financial truth.

Future Go API must call equivalent application use cases, not duplicate domain logic.

Application services own orchestration.

Domain services own invariants.

Adapters own persistence mapping.

Reports must use explicit version or mode when historical/current semantics differ.

### Audit

audit_events and audit_event_snapshots are the preferred canonical audit spine.

audit_logs may remain legacy or compatibility storage.

New finance-sensitive mutations should target canonical audit_events when the writer contract is available.

Audit must support a future unified UI timeline.

Required audit facts:

- actor id
- actor role
- reason
- event name
- aggregate type
- aggregate id
- source id
- occurred at
- before snapshot when relevant
- after snapshot when relevant

## Current Scope Impact

This ADR affects future schema and application design for:

- note revision settlement
- surplus disposition
- refund due
- refund paid
- customer credit
- customer balance entries
- audit events
- transaction reports
- future API

It does not authorize immediate migration to PostgreSQL.

It does not authorize immediate Go API implementation.

It does not authorize broad rewrite.

## Required Migration Discipline for New Tables

Every new finance-sensitive migration must document:

- table purpose
- business date field if applicable
- occurred_at field if action-based
- created_at and updated_at policy
- source id fields
- actor/audit linkage if sensitive
- state/status allowed values
- money columns
- indexes and read paths
- deletion policy
- legacy compatibility
- future PostgreSQL concerns

## Surplus Disposition Application

ADR 0027 applies this policy to note revision surplus disposition.

For note_revision_surplus_dispositions:

- use string id
- use string status
- use integer rupiah
- use explicit occurred_at
- use created_at
- use updated_at policy
- use source settlement id
- use note root id
- use note revision id
- link to canonical audit event
- avoid MySQL enum
- avoid JSON as financial truth
- index source and note-root read paths
- restrict delete if foreign key is practical

## Rejected Behaviors

The following are rejected:

- adding MySQL enum status columns for finance domain state
- relying only on unsigned integer columns for money invariants
- using created_at as transaction date
- using transaction_date as audit event time
- storing final financial state only in JSON
- cascade deleting financial ledger or audit history
- creating UI-only financial truth
- creating API-only duplicate business logic
- implementing PostgreSQL before source audit and migration plan
- rewriting MySQL schema broadly during refund/edit slice without proof

## Consequences

Positive consequences:

- current MySQL work remains disciplined
- future PostgreSQL migration has fewer traps
- future Go API can reuse application contracts
- audit and timeline can become unified
- reporting can stay explicit about current versus historical meaning

Costs:

- migrations require more design before implementation
- some quick MySQL shortcuts are forbidden
- audit writer and projection design must be handled deliberately
- future migration remains a separate project, not a side effect of refund/edit work

## Remaining Gaps

This ADR intentionally leaves two gaps for future work:

1. Full PostgreSQL migration plan.
2. Go API endpoint contract.

Neither gap blocks the current refund/edit source-audit and surplus disposition planning.

## Next Safe Step

Use this ADR as a checklist when designing the first note_revision_surplus_dispositions migration.

Do not implement PostgreSQL in the current slice.

Do not implement Go API in the current slice.

