# Handoff 0009 - Surplus Refund Due Use Case

## Metadata

- Date: 2026-05-13
- Sequence: 0009
- Scope: refund_due surplus disposition application use case
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0008_surplus_disposition_backend_foundation_handoff.md
- Status: refund_due use case implemented and locally verified with targeted plus focused backend proof
- Owner workflow: owner handles commit and push manually

## Session Goal

Continue HyperPOS edit/refund sniper chain from handoff 0008.

The active target was to build backend application use case:

    CreateNoteRevisionSurplusRefundDue

The goal was not UI, controller, route, report query, refund_paid execution, customer_credit, customer_balance_entries, PostgreSQL, or Go API.

## Baseline Facts

Owner baseline facts accepted for this session:

- Owner always commits and pushes manually.
- Local and repo are identical after push except ignored files.
- Owner statement clean, pushed, latest, or make verify pass is FACT.
- Local command output and owner statement win over GitHub or docs when there is conflict.
- Do not ask for git status, git log, git diff, git diff --check, or make verify as ritual.
- Git and make verify are used only when there is a real trigger.

Required source files read before implementation planning:

- docs/01_standards/0001_index.md
- docs/01_standards/0002_decision_policy.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md
- docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0008_surplus_disposition_backend_foundation_handoff.md
- docs/02_architecture/adr/0026_note_revision_surplus_disposition.md
- docs/02_architecture/adr/0027_note_revision_surplus_disposition_transaction_contract.md
- docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md

## Decision Used

Decision source:

- ADR 0026
- ADR 0027
- ADR 0028
- handoff 0008
- current source proof from targeted and focused tests

Locked decisions:

- refund_due-only remains the active implementation target.
- refund_due is a disposition decision, not refund_paid.
- refund_due does not mean money already left the business.
- customer_credit remains blocked until customer identity is locked.
- customer_balance_entries remains out of scope.
- refund_paid execution remains out of scope.
- audit_events and audit_event_snapshots are canonical for this new finance-sensitive audit.
- audit_logs remains legacy or compatibility storage.
- UI is not financial truth.
- Controller and route are out of scope until backend use case contract is stable.
- Report query is out of scope until backend truth exists.
- PostgreSQL implementation is out of scope.
- Go API implementation is out of scope.

## Completed Work

New files created:

- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueCommand.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueResult.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueGuard.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueAuditEventFactory.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php

Behavior implemented:

- Rejects non-admin actor.
- Rejects empty reason.
- Rejects invalid amount less than or equal to zero.
- Rejects missing pending settlement.
- Rejects non-overpaid-pending or resolved pending settlement.
- Rejects amount greater than unresolved pending surplus.
- Generates disposition id using UuidPort.
- Generates audit event id using UuidPort.
- Uses ClockPort for occurred_at and created_at.
- Loads unresolved pending using NoteRevisionSurplusDispositionReaderPort.
- Writes canonical audit event using AuditEventWriterPort.
- Writes before and after audit snapshots.
- Writes note_revision_surplus_dispositions using NoteRevisionSurplusDispositionWriterPort.
- Uses TransactionManagerPort begin, commit, and rollBack.
- Returns stable result data including disposition id, settlement id, note root id, note revision id, disposition type, amount, before pending, after pending, unresolved pending, status, and audit event id.
- Rolls back audit event, audit snapshots, and disposition row when the second write fails.

## Source Shape

Application files:

- Command object owns input contract.
- Result object owns success/failure response.
- Guard owns validation and permission checks.
- Audit event factory owns audit payload and snapshot creation.
- Handler owns orchestration and transaction boundary.

Hexagonal boundary:

- Application use case depends on outbound ports.
- Persistence remains behind adapters.
- No controller logic added.
- No Blade logic added.
- No report query added.
- No direct audit_logs write added.

Ports used:

- App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort
- App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort
- App\Ports\Out\AuditEventWriterPort
- App\Ports\Out\TransactionManagerPort
- App\Ports\Out\UuidPort
- App\Ports\Out\ClockPort

## Proof

Syntax proof:

    No syntax errors detected in app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueCommand.php
    No syntax errors detected in app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueResult.php
    No syntax errors detected in app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueGuard.php
    No syntax errors detected in app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueAuditEventFactory.php
    No syntax errors detected in app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php
    No syntax errors detected in tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php

Targeted use case proof:

    PASS  Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueHandlerTest
    ✓ rejects non admin actor
    ✓ rejects empty reason
    ✓ rejects missing or invalid pending settlement
    ✓ rejects amount greater than unresolved pending
    ✓ writes audit event snapshots disposition and updates pending
    ✓ rolls back audit event and disposition when second write fails

    Tests: 6 passed (26 assertions)
    Duration: 5.25s

Focused backend contract proof:

    PASS  Tests\Feature\AuditLog\DatabaseAuditEventWriterAdapterTest
    ✓ writer persists audit event with before and after snapshots
    ✓ writer rejects duplicate snapshot kind before database write
    ✓ writer participates in outer database transaction

    PASS  Tests\Feature\Database\NoteRevisionSurplusDispositionMigrationTest
    ✓ note revision surplus dispositions table exists with expected columns
    ✓ note revision surplus dispositions indexes and foreign keys exist

    PASS  Tests\Feature\Note\DatabaseNoteRevisionSurplusDispositionAdapterTest
    ✓ writer persists refund due surplus disposition
    ✓ reader returns unresolved pending after active disposition
    ✓ reader ignores non overpaid pending settlement

    PASS  Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueHandlerTest
    ✓ rejects non admin actor
    ✓ rejects empty reason
    ✓ rejects missing or invalid pending settlement
    ✓ rejects amount greater than unresolved pending
    ✓ writes audit event snapshots disposition and updates pending
    ✓ rolls back audit event and disposition when second write fails

    Tests: 14 passed (77 assertions)
    Duration: 5.45s

## Tests Run

Targeted:

    php artisan test tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php

Focused:

    php artisan test \
      tests/Feature/AuditLog/DatabaseAuditEventWriterAdapterTest.php \
      tests/Feature/Database/NoteRevisionSurplusDispositionMigrationTest.php \
      tests/Feature/Note/DatabaseNoteRevisionSurplusDispositionAdapterTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php

Not run:

- make verify
- browser/manual QA
- UI route tests
- report tests

Reason:

This was backend application use case closure, not final public safe-state closure. No UI/controller/report was added.

## Files Not Touched

- routes/*
- resources/*
- app/Adapters/In/Http/*
- app/Adapters/Out/Reporting/*
- app/Ports/Out/AuditLogPort.php
- app/Adapters/Out/Audit/DatabaseAuditLogAdapter.php
- database/migrations/*
- docs/02_architecture/adr/* during source implementation

## Residual Gaps

Blocking before UI/controller:

- Need decide transport input shape for admin action.
- Need decide how admin UI obtains pending settlement id.
- Need decide UI failure messages and redirect/response behavior.
- Need route/controller test before adding route.
- Need verify route uses the same application use case, not duplicate logic.

Not blocking backend use case closure:

- customer identity contract, because customer_credit is out of scope.
- customer_balance_entries, because customer_credit and credit_used are out of scope.
- refund_paid execution, because refund_due is not refund_paid.
- PostgreSQL implementation.
- Go API implementation.
- Report query.

Technical debt or future hardening:

- Reader currently computes pending from settlement and active disposition sum, but does not explicitly lock settlement row. Concurrency hardening may need a later source audit before multi-admin or high-contention use.
- No idempotency token yet for repeated refund_due submit.
- No reversal or cancel-refund-due use case yet.

## Current State

Backend foundation now has:

- canonical audit writer foundation
- surplus disposition migration
- surplus disposition DTOs
- surplus disposition reader/writer ports
- surplus disposition DB adapter
- refund_due application use case
- targeted use case proof
- focused backend contract proof

Backend foundation does not yet have:

- controller
- route
- Blade UI
- admin action form
- report query
- refund_paid execution
- customer_credit
- customer_balance_entries
- PostgreSQL implementation
- Go API implementation

## Next Active Step

Recommended next step:

Design the minimum admin transport slice for invoking CreateNoteRevisionSurplusRefundDue.

Scope in:

- source audit only for relevant admin note/revision routes/controllers/views
- decide endpoint shape
- decide request fields
- decide response behavior
- decide auth/admin boundary reuse
- decide test plan before production patch

Scope out:

- no route implementation before blueprint
- no Blade implementation before controller/use case boundary is clear
- no report query
- no refund_paid
- no customer_credit
- no customer_balance_entries
- no PostgreSQL
- no Go API

Minimum files to inspect next:

- relevant admin note/revision controllers
- relevant admin note/revision routes
- relevant admin note/revision views only after controller path is identified
- current admin middleware or access boundary for admin-only action

Expected next proof before patch:

- source anchors showing existing admin route/controller pattern
- selected endpoint shape
- files to touch
- files not to touch
- rollback/containment plan
- targeted controller/use case integration test plan

## Session Context Health

73 percent.

Reason:

The backend surplus disposition chain now includes several new foundation files plus a new use case, targeted proof, focused proof, and a changed next active step. A mini-summary is required in future project-work responses until a new session or context reset.
