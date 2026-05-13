# ADR 0027 - Note Revision Surplus Disposition Transaction Contract

## Status

Accepted for next implementation planning.

## Context

ADR 0026 locks the business meaning of note revision surplus.

A downward paid revision can create surplus.

The base holding state is overpaid_pending.

overpaid_pending is not revenue.

overpaid_pending is not automatic refund paid.

overpaid_pending is not automatic customer credit.

The next safe implementation slice needs a transaction contract that does not overload note_revision_settlements and does not require customer identity work before it is ready.

Current source audit found:

- notes stores customer_name, customer_phone, and transaction_date.
- notes does not currently prove stable customer_id or customer_key.
- note_revision_settlements already stores revision settlement snapshot.
- NoteRevisionSettlement currently supports underpaid, paid, and overpaid_pending.
- customer_refunds exists for actual refund records, but it does not currently reference note_revision_settlement_id or surplus disposition.
- audit_events and audit_event_snapshots exist and are the preferred direction for a single canonical audit spine.
- audit_logs exists as a generic legacy or compatibility audit store.

## Problem

If surplus disposition is implemented directly inside note_revision_settlements, the system will mix immutable settlement snapshot with later operational decisions.

If customer_balance_entries is implemented before stable customer identity exists, customer credit can be assigned to the wrong person.

If customer_refunds is reused immediately for pending surplus, the system can fake refund execution before money actually leaves the business.

If audit is stored only in loose JSON logs, later UI timeline, future API, and PostgreSQL migration will inherit weak audit semantics.

These shortcuts are rejected.

## Decision

The first implementation slice is refund_due-only surplus disposition foundation.

The selected first transaction table is:

    note_revision_surplus_dispositions

note_revision_settlements remains the immutable revision settlement snapshot.

note_revision_surplus_dispositions records explicit operational disposition decisions for surplus created by note revision settlement.

The first allowed disposition type is:

    refund_due

The following are intentionally blocked in the first implementation slice:

- customer_credit
- refund_paid execution
- credit_used execution
- split UI
- cashier disposition
- manual adjustment
- customer_balance_entries
- PostgreSQL implementation
- Go API implementation

## Table Contract

Recommended table:

    note_revision_surplus_dispositions

Required columns:

    id
    note_revision_settlement_id
    note_root_id
    note_revision_id
    disposition_type
    amount_rupiah
    before_pending_rupiah
    after_pending_rupiah
    status
    occurred_at
    created_at
    updated_at
    audit_event_id

Column meaning:

    id
        Primary identity for the disposition row.

    note_revision_settlement_id
        Source settlement snapshot that created the surplus.

    note_root_id
        Root note id for fast lookup and traceability.

    note_revision_id
        Revision id related to the source settlement.

    disposition_type
        First supported value: refund_due.

    amount_rupiah
        Positive integer rupiah amount disposed from pending surplus.

    before_pending_rupiah
        Pending surplus amount before this disposition.

    after_pending_rupiah
        Pending surplus amount after this disposition.

    status
        Lifecycle status for this disposition row.
        First supported value: active.

    occurred_at
        Domain action timestamp.

    created_at
        Row creation timestamp.

    updated_at
        Row update timestamp.
        It may stay nullable or equal to created_at if the row is immutable in the first slice.

    audit_event_id
        Reference to the canonical audit event when audit writer contract is locked.

## Invariants

A surplus disposition is valid only if:

- source settlement exists
- source settlement status is overpaid_pending
- amount_rupiah is greater than zero
- amount_rupiah is not greater than currently unresolved pending surplus
- before_pending_rupiah is not negative
- after_pending_rupiah is not negative
- after_pending_rupiah equals before_pending_rupiah minus amount_rupiah
- total active disposition amount for one settlement never exceeds settlement surplus_rupiah
- disposition_type is supported by the current slice
- actor id is known through audit event or transaction context
- actor role is known through audit event or transaction context
- reason is not empty
- occurred_at is explicit
- mutation is transaction-bound

## Refund Due Rule

refund_due means the business has explicitly decided that the pending surplus should become refundable liability.

refund_due is not refund_paid.

refund_due does not mean money already left the business.

refund_due is already disposed from unresolved pending surplus.

A later note revision must not silently consume money that has already become refund_due.

If the business needs to cancel or reverse refund_due, that requires a future explicit reversal or cancel-refund-due use case.

The first implementation slice must not silently reclaim refund_due during later revision settlement recalculation.

## Customer Credit Rule

customer_credit is blocked until customer identity is stable.

The current source only proves customer_name and customer_phone on notes.

customer_name is not a safe customer key.

customer_phone is nullable and not enough as a stable customer identity.

A future customer identity contract must decide:

- customers table or equivalent identity table
- customer_key generation
- phone normalization
- anonymous or walk-in customer handling
- legacy note compatibility
- customer merge or correction policy if needed

Until that contract exists, customer_credit and customer_balance_entries are out of scope.

## Permission Rule

The first implementation slice is admin-only.

The current business/system shape has one admin.

No dedicated surplus disposition capability is required in the first slice.

Do not reuse admin_transaction_entry capability for surplus disposition.

If multi-admin, cashier disposition, manual adjustment, or high-trust delegated disposition becomes necessary, create a separate ADR and capability policy.

## Audit Rule

Surplus disposition is a sensitive finance action.

The preferred canonical audit direction is:

    audit_events
    audit_event_snapshots

audit_events is the single audit spine for future UI timeline and API reads.

audit_event_snapshots stores before and after snapshots when needed.

audit_logs remains legacy or generic compatibility and must not become the final finance audit truth for this slice.

The disposition write use case should eventually create a structured audit event in the same transaction as the disposition row.

Minimum audit facts required:

- actor id
- actor role
- reason
- event name
- aggregate type
- aggregate id
- source note id
- source revision id
- source settlement id
- disposition id
- disposition type
- amount
- before pending amount
- after pending amount
- occurred at

If canonical audit writer is not ready, implementation must stop before production mutation.

Do not hide this as a loose JSON-only audit.

## Temporal Semantics Policy

Financial and operational tables must distinguish date meanings.

transaction_date means the business date of the note or transaction.

effective_date means the business effect date when a future slice needs a date different from the note transaction date.

occurred_at means when the domain action happened.

created_at means when the row was created.

updated_at means when the row was changed.

For new financial or action tables, occurred_at and created_at are required.

For business documents or business effects, transaction_date or effective_date must be explicit when relevant.

Do not use created_at as a substitute for business date.

Do not use transaction_date as a substitute for audit timestamp.

Do not use updated_at as proof that a business event occurred.

## MySQL and Future PostgreSQL Discipline

The current implementation remains MySQL.

PostgreSQL is not implemented in this slice.

However, new MySQL migrations must avoid creating future PostgreSQL migration debt.

Rules:

- do not use MySQL enum for domain status
- use string status plus domain validation
- use integer rupiah for money
- avoid unsigned-only assumptions in domain logic that would make PostgreSQL migration awkward
- use explicit columns for financial truth
- use JSON only for metadata or snapshots, not primary financial truth
- keep timestamps explicit
- avoid DB-specific generated behavior unless justified
- index actual read paths
- use restrict-on-delete foreign keys where practical
- do not cascade delete financial history
- keep IDs string-compatible with future API boundaries
- keep read models indexable for sub-one-second UI/API reads

## API Readiness Policy

The first slice is not an API implementation.

However, the transaction contract must be usable by future Blade and API transports.

Controllers must remain transport adapters.

Future API must call the same application use case as Blade.

UI must not compute final disposition amount.

UI must not decide final refund truth.

The use case must own the mutation rule.

Adapters must own persistence mapping only.

## Expected First Use Case Shape

Future use case name may be adjusted after source audit, but the shape should remain:

    CreateNoteRevisionSurplusRefundDue

Input:

    note_revision_settlement_id
    amount_rupiah
    reason
    actor_id
    actor_role
    occurred_at

Output:

    disposition_id
    note_revision_settlement_id
    note_root_id
    note_revision_id
    disposition_type
    amount_rupiah
    before_pending_rupiah
    after_pending_rupiah
    status

Required behavior:

- load settlement
- compute unresolved pending surplus
- reject invalid amount
- reject unsupported disposition type
- require reason
- require admin actor
- write disposition
- write canonical audit event and snapshot when writer contract is ready
- commit inside transaction
- return stable DTO or result object

## Rejected Behaviors

The following are rejected:

- mutating note_revision_settlements into a lifecycle ledger
- treating refund_due as refund_paid
- treating overpaid_pending as customer credit
- creating customer_balance_entries before customer identity is locked
- using customer_name as customer key
- silently consuming refund_due in later revision
- using admin_transaction_entry as surplus disposition permission
- storing final finance audit only in loose audit_logs JSON
- relying on UI to compute disposition amount
- implementing PostgreSQL before the current MySQL contract is clean
- implementing Go API before use case contract is stable
- using created_at as business date
- using transaction_date as audit timestamp

## Consequences

Positive consequences:

- settlement snapshot remains clean
- surplus disposition becomes explicit
- refund_due can be implemented without customer identity risk
- future customer credit remains possible
- future audit timeline has a canonical direction
- future PostgreSQL migration is protected from avoidable MySQL debt
- future Go API can reuse the same application contract

Costs:

- refund_paid execution remains unfinished
- customer_credit remains blocked
- audit writer contract must be inspected before production mutation
- extra table is required
- later reporting/UI must explicitly display pending and disposition states

## Remaining Gaps

Only two gaps block implementation planning:

1. Canonical audit writer contract must be audited and locked.
2. Customer identity contract must be audited and locked before customer_credit.

Customer identity does not block refund_due-only implementation.

Audit writer contract blocks production mutation if audit_event_id is required in the first slice.

## Next Safe Step

Read the existing audit_events source and decide whether the first surplus disposition implementation can write canonical audit_events and audit_event_snapshots now.

Do not start from UI.

Do not start from report query.

Do not create customer_balance_entries.

Do not implement PostgreSQL.

Do not implement Go API.

