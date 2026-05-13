# Handoff 0007 - Surplus Disposition Source Audit

## Metadata

- Date: 2026-05-13
- Sequence: 0007
- Scope: surplus disposition source audit, transaction ADR, and migration readiness ADR
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0006_surplus_disposition_adr_handoff.md
- Latest proven commit or push proof: Owner handles commit and push manually. This handoff is docs-only patch content until owner applies and verifies it.

## Status

planning and docs-only update

## Session Goal

Compress surplus disposition implementation gaps before production code.

The session turned the broad gap list into two ADRs:

- ADR 0027 for note revision surplus disposition transaction contract
- ADR 0028 for MySQL to PostgreSQL and API migration readiness

The goal is to make the next implementation session start from narrowed source audit, not broad rediscovery.

## Facts

Source and docs read in the session chain:

- docs/01_standards/0001_index.md
- docs/01_standards/0002_decision_policy.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md
- docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
- docs/99_archive/handoff/v2/edit_refund_sniper/PROMPT_TEMPLATE.md
- docs/99_archive/handoff/v2/edit_refund_sniper/HANDOFF_TEMPLATE.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0006_surplus_disposition_adr_handoff.md
- docs/02_architecture/adr/0026_note_revision_surplus_disposition.md
- docs/03_blueprints/finance/0006_note_revision_refund_ledger.md
- docs/03_blueprints/finance/0007_note_revision_refund_ledger_dod.md
- docs/03_blueprints/finance/0008_note_revision_refund_ledger_workflow.md

Source audit facts:

- notes currently stores customer_name, customer_phone, and transaction_date.
- notes does not currently prove stable customer_id or customer_key.
- Note core object is created with customer name and phone, not a customer entity.
- note_revisions stores transaction_date, created_at, and updated_at.
- note_revision_settlements exists.
- note_revision_settlements stores settlement snapshot fields and settlement_status.
- NoteRevisionSettlement supports underpaid, paid, and overpaid_pending.
- BuildNoteRevisionSettlement computes net paid, outstanding, surplus, and status.
- BuildCreateNoteRevisionSettlement reads component payment/refund first and falls back to legacy allocation/refund.
- DatabaseNoteRevisionSettlementAdapter reads and writes note_revision_settlements.
- CreateNoteRevisionCommitter creates revision, creates settlement if present, sets current revision, and records note_revision_created audit.
- audit_logs exists as generic event plus JSON context storage.
- audit_events and audit_event_snapshots exist with actor, role, reason, aggregate, occurred_at, metadata, and snapshot payload.
- customer_refunds exists for actual refund records.
- customer_refunds has customer_payment_id, note_id, amount_rupiah, refunded_at, and reason.
- customer_refunds does not currently prove note_revision_settlement_id, surplus disposition id, actor id, or actor role.
- admin_transaction_capability_states exists for admin_transaction_entry.
- TransactionEntryPolicy allows kasir transaction entry by default and requires capability only for admin transaction entry.
- Owner stated the current system has one admin.
- Owner wants strict hexagonal discipline.
- Owner wants a single audit log or audit timeline standard for all actions.
- Owner wants future migration to PostgreSQL and Go API to be taken seriously.
- Owner does not want PostgreSQL implementation now.
- Owner wants current MySQL migrations to be arranged so future PostgreSQL migration does not become technical debt.

## Gaps

Implementation-blocking gaps are reduced to:

1. Canonical audit writer contract is not yet locked.
   - audit_events and audit_event_snapshots exist.
   - The next session must inspect the writer/port path and decide whether surplus disposition can write canonical audit_events in the first production slice.
   - If canonical audit writer is not ready, stop before production mutation.

2. Customer identity contract is not yet locked.
   - customer_credit is blocked until customer identity or customer_key is designed.
   - This does not block refund_due-only surplus disposition.

Non-blocking future gaps:

- PostgreSQL migration plan is not implemented.
- Go API endpoint contract is not implemented.
- refund_paid execution path is not implemented.
- customer_balance_entries is not implemented.
- UI display is not implemented.
- reporting visibility is not implemented.

## Assumptions

No implementation assumption accepted.

The current patch is docs-only.

No production code is changed.

No database migration is created.

## Decisions

Decision source: owner statement.

- PostgreSQL migration readiness is serious.
- PostgreSQL is not implemented now.
- Current MySQL migrations must avoid preventable PostgreSQL migration debt.
- Full API or Go API is future scope.
- Current scope remains refund and edit chain first.
- If transaction migration is tightly coupled to refund/edit correctness, audit first, then conclude.

Decision source: owner statement and source audit.

- Customer identity must be done correctly.
- customer_name is not accepted as a safe customer key for customer_credit.
- customer_credit is blocked until customer identity contract is locked.

Decision source: owner statement.

- First slice permission is admin-only because the system currently has one admin.
- Dedicated capability is not required in the first surplus disposition slice.
- Do not reuse admin_transaction_entry as surplus disposition permission.

Decision source: ADR 0026 and current source audit.

- note_revision_settlements remains settlement snapshot.
- note_revision_surplus_dispositions is selected as the first transaction table direction.
- First supported disposition target is refund_due.
- refund_due is disposed from unresolved pending surplus.
- Later revision must not silently consume refund_due.
- refund_paid execution is out of scope.

Decision source: owner statement and audit source audit.

- audit_events and audit_event_snapshots are the preferred canonical audit spine.
- audit_logs remains legacy or generic compatibility.
- Future UI should read actions from one canonical audit or timeline source.
- Loose JSON-only audit is not accepted as final finance audit truth.

Decision source: owner statement and migration-readiness rule.

- New MySQL finance-sensitive migrations must be PostgreSQL-ready.
- Do not use MySQL enum for domain state.
- Use explicit columns for financial truth.
- JSON is allowed for metadata or snapshots, not primary financial truth.
- Date semantics must be explicit.

## Active Slice

Selected active slice:

Surplus disposition source audit and docs decision lock.

Scope in:

- Create ADR 0027 for transaction contract.
- Create ADR 0028 for MySQL to PostgreSQL and API migration readiness.
- Create handoff 0007.
- Update README latest handoff pointer.
- Record next implementation audit files.
- Reduce blocker gaps to canonical audit writer and customer identity.

Scope out:

- production app code
- database migration
- UI
- controller
- report query
- refund_paid execution
- customer_credit
- customer_balance_entries
- PostgreSQL implementation
- Go API implementation
- broad repo rewrite

Files to touch:

- docs/02_architecture/adr/0027_note_revision_surplus_disposition_transaction_contract.md
- docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0007_surplus_disposition_source_audit_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md

Files not touched:

- app/*
- database/*
- routes/*
- resources/*
- public/*
- tests/*

DB impact:

- None in this docs-only slice.
- Future DB impact is expected for note_revision_surplus_dispositions only after canonical audit writer contract is locked or explicitly scoped.

UI impact:

- None in this slice.
- Future UI should read from a canonical audit or timeline source, not compute financial truth.

Report impact:

- None in this slice.
- Future reporting must distinguish pending surplus, refund due, refund paid, customer credit, and credit used when those states are in scope.

API impact:

- None in this slice.
- ADR 0028 requires application contracts to remain usable by future API.

Audit impact:

- audit_events and audit_event_snapshots are selected as preferred canonical audit spine.
- audit_logs remains legacy or generic compatibility.
- Next implementation audit must inspect existing writer paths before mutation.

## Source Audit Summary

path: database/migrations/2026_03_14_000100_create_notes_table.php
current behavior: notes stores customer_name, customer_phone, transaction_date, note state, and total.
risk: no stable customer id or customer key for customer_credit.
scope: source proof for blocking customer_credit.

path: app/Core/Note/Note/Note.php
current behavior: Note is created and rehydrated with customer name and customer phone.
risk: customer identity is embedded as note text, not stable customer entity.
scope: source proof for blocking customer_credit.

path: app/Application/Note/UseCases/CreateNoteHandler.php
current behavior: creates Note from customerName, customerPhone, and transactionDate.
risk: use case does not establish customer identity.
scope: source proof for customer identity gap.

path: database/migrations/2026_04_22_000001_create_note_revisions_table.php
current behavior: note_revisions stores transaction_date, created_at, updated_at, actor id, reason, customer snapshot, and totals.
risk: actor role is not proven in revision table.
scope: temporal semantics proof.

path: database/migrations/2026_05_13_000100_create_note_revision_settlements_table.php
current behavior: stores settlement snapshot with gross, carry forward, net, outstanding, surplus, status, created_at, and updated_at.
risk: table should not be mutated into lifecycle ledger.
scope: source proof for separate disposition table.

path: app/Application/Note/DTO/NoteRevisionSettlement.php
current behavior: supports underpaid, paid, and overpaid_pending.
risk: no refund_due or customer_credit lifecycle here.
scope: source proof that disposition belongs outside settlement DTO.

path: app/Application/Note/Services/BuildNoteRevisionSettlement.php
current behavior: computes settlement amounts and status.
risk: builder is settlement snapshot logic, not disposition logic.
scope: source proof for keeping settlement clean.

path: app/Application/Note/Services/BuildCreateNoteRevisionSettlement.php
current behavior: reads payment/refund carry-forward from component-first or legacy fallback readers.
risk: later disposed money must not be silently consumed by this path.
scope: future implementation audit target.

path: app/Adapters/Out/Note/DatabaseNoteRevisionSettlementAdapter.php
current behavior: persists and reads note_revision_settlements.
risk: no disposition read model.
scope: future source audit target.

path: app/Application/Note/UseCases/CreateNoteRevisionCommitter.php
current behavior: writes revision, writes settlement, sets current revision, writes generic audit.
risk: future disposition must not be hidden in revision commit.
scope: future source audit target.

path: database/migrations/2026_04_06_230100_create_audit_events_and_snapshots_tables.php
current behavior: audit_events and audit_event_snapshots exist with structured fields and snapshots.
risk: writer path not yet locked.
scope: next implementation audit blocker.

path: database/migrations/2026_03_10_000300_create_audit_logs_table.php
current behavior: generic audit_logs with event and JSON context.
risk: too loose as final finance audit truth.
scope: legacy or compatibility only.

path: database/migrations/2026_03_15_000100_create_customer_refunds_table.php
current behavior: actual refund table stores customer_payment_id, note_id, amount, refunded_at, and reason.
risk: does not model pending surplus disposition or source settlement.
scope: refund_paid future audit target, not current slice.

path: database/migrations/2026_03_10_000200_create_admin_transaction_capability_states_table.php
current behavior: stores admin transaction capability active state.
risk: capability is for transaction entry, not surplus disposition.
scope: source proof for not reusing this capability.

path: app/Application/IdentityAccess/Policies/TransactionEntryPolicy.php
current behavior: kasir allowed by default; admin needs transaction capability.
risk: not appropriate for surplus disposition.
scope: source proof for admin-only first slice.

## Files Changed

Planned docs-only files:

- docs/02_architecture/adr/0027_note_revision_surplus_disposition_transaction_contract.md
- docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0007_surplus_disposition_source_audit_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md

No production files changed.

## Tests And Proof

No tests run in this docs-only planning patch.

Expected local proof after owner applies patch:

    grep -RIn -e "note_revision_surplus_dispositions" -e "refund_due-only" -e "customer_credit" -e "audit_events" -e "PostgreSQL" -e "occurred_at" -e "transaction_date" docs/02_architecture/adr/0027_note_revision_surplus_disposition_transaction_contract.md docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md docs/99_archive/handoff/v2/edit_refund_sniper/0007_surplus_disposition_source_audit_handoff.md docs/99_archive/handoff/v2/edit_refund_sniper/README.md

Expected result:

- anchors appear in ADR 0027
- anchors appear in ADR 0028
- anchors appear in handoff 0007
- README latest handoff pointer points to 0007

Markdown fence safety proof:

    no output from fence scanner

## Residual Risks

Blocks next production implementation:

- canonical audit writer contract not locked

Does not block refund_due-only planning:

- customer identity contract not locked, because customer_credit is out of scope

Needs owner decision later:

- whether customer identity is implemented as customers table or another identity model
- whether future multi-admin requires dedicated surplus disposition capability
- whether refund_due reversal is needed before refund_paid execution

Future improvement:

- customer identity foundation
- customer_balance_entries
- refund_paid execution from refund_due
- credit_used execution
- unified audit timeline UI
- PostgreSQL migration project
- Go API project

## Next Active Step

Goal:

Audit canonical audit writer contract for audit_events and audit_event_snapshots.

Suggested source targets:

    database/migrations/2026_04_06_230100_create_audit_events_and_snapshots_tables.php
    app/Ports/Out/AuditLogPort.php
    app/Adapters/Out/Audit/DatabaseAuditLogAdapter.php
    app/Adapters/Out/Audit/*
    app/Providers/HexagonalServiceProvider.php
    tests/Feature/Database/V2AuditFoundationMigrationTest.php
    tests/Feature/AuditLog/*

Expected proof:

- whether a structured audit event writer already exists
- whether the first surplus disposition implementation can write audit_events in one transaction
- whether audit_event_snapshots can store before and after pending surplus snapshots
- whether audit_logs must remain compatibility-only
- exact files to touch for the first production slice

Stop condition:

Stop before migration if canonical audit writer is missing or unclear.

Do not fallback to loose JSON-only finance audit.

## Next Session Opening Prompt

    Kita lanjut HyperPOS dari edit/refund sniper handoff.

    Baca berurutan:
    docs/01_standards/0001_index.md
    docs/01_standards/0002_decision_policy.md
    docs/99_archive/handoff/v2/edit_refund_sniper/README.md
    docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
    docs/99_archive/handoff/v2/edit_refund_sniper/0007_surplus_disposition_source_audit_handoff.md
    docs/02_architecture/adr/0026_note_revision_surplus_disposition.md
    docs/02_architecture/adr/0027_note_revision_surplus_disposition_transaction_contract.md
    docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md

    Baseline FACT:
    - Saya selalu push setiap aksi.
    - Local dan repo identik setelah push kecuali ignored files.
    - Kalau saya menyatakan clean, pushed, latest, atau make verify pass, itu FACT.
    - Local command output dan owner statement menang atas GitHub/docs kalau ada konflik.
    - Jangan minta git status/log/diff/diff --check/make verify sebagai ritual.
    - Git dan make verify hanya dipakai kalau ada trigger nyata.

    Latest completed:
    - ADR 0026 accepted surplus disposition domain model.
    - ADR 0027 locks refund_due-only surplus disposition transaction contract.
    - ADR 0028 locks MySQL-to-PostgreSQL and API migration readiness discipline.
    - customer_credit is blocked until customer identity is locked.
    - customer_balance_entries is out of scope.
    - PostgreSQL implementation is out of scope.
    - Go API implementation is out of scope.

    Current active target:
    - Audit canonical audit writer contract for audit_events and audit_event_snapshots.
    - Do not start from UI.
    - Do not start from report query.
    - Do not create migration yet.
    - Do not use audit_logs JSON as final finance audit truth.

    Required response shape:
    FACT
    GAP
    ASSUMPTION
    DECISION
    ACTIVE STEP
    FILES TO TOUCH
    FILES NOT TO TOUCH
    COMMAND
    EXPECTED PROOF
    NEXT

## README Update Required

Yes.

README latest handoff pointer must point to:

    docs/99_archive/handoff/v2/edit_refund_sniper/0007_surplus_disposition_source_audit_handoff.md

## Session Context Health

76 percent.

Reason:

This session locked surplus disposition transaction direction, migration-readiness constraints, temporal semantics, and narrowed remaining blockers. Next session should start from this handoff before any implementation.

