# Handoff 0015 - Refund Paid Backend Foundation

## Metadata

- Date: 2026-05-13
- Sequence: 0015
- Scope: refund_paid backend foundation from refund_due using note_revision_surplus_refund_payments
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0014_refund_paid_contract_and_migration_handoff.md
- Latest pushed baseline before this slice: 8780f720, commit 1976, provided by owner
- Owner workflow: owner handles commit and push manually

## Status

Implementation partial but backend foundation is locally green.

refund_paid execution backend foundation now exists for refund_due.

This slice does not implement UI, report, export, cash ledger, audit timeline display, reversal, customer credit, customer balance entries, PostgreSQL, or Go API.

## Baseline Proof

Owner provided baseline proof before this slice:

- Handoff 0014 existed.
- README latest handoff pointer was updated to 0014.
- Tests were green: 1000 passed / 5348 assertions.
- Handoff docs were pushed to origin main through make push.
- Latest pushed commit was 8780f720, commit 1976.
- A stray shell marker produced bash: EOF: command not found after grep handoff proof, but the handoff file existed, README was updated, and make push succeeded. It was treated as a shell stray marker, not a blocker.

## Locked Decisions

refund_paid from refund_due uses:

    note_revision_surplus_refund_payments

Do not use:

- customer_refunds
- customer_payment_id
- refund_component_allocations
- note refunded lifecycle
- inventory reversal
- customer_credit
- customer_balance_entries
- PostgreSQL implementation
- Go API implementation
- UI/report/export as source of truth

Owner handles commit and push manually.

## Facts From Source Audit

Local audit showed note_revision_surplus_refund_payments appeared only in:

- database migration
- ADR 0029
- handoff docs

No source code for DTO, port, adapter, or use case existed before this slice.

Local audit showed:

- NoteRevisionSurplusDispositionReaderPort had only pending lookup methods.
- DatabaseNoteRevisionSurplusDispositionAdapter had no lockForUpdate source disposition path.
- Existing forbidden customer refund and inventory paths remained separate:
  - RecordCustomerRefundTransaction
  - RecordCustomerRefundOperation
  - AllocateRefundAcrossComponents
  - AutoRefundNoteWhenFullyRefunded
  - AutoReverseRefundedStoreStockInventory

## Files Added Or Changed

New DTOs:

- app/Application/Note/DTO/NoteRevisionSurplusRefundPayment.php
- app/Application/Note/DTO/NoteRevisionSurplusRefundDueSource.php

New use case objects:

- app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentCommand.php
- app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentResult.php
- app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentResultFactory.php
- app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentGuard.php
- app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentFactory.php
- app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentAuditEventFactory.php
- app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentHandler.php

New ports:

- app/Ports/Out/Note/NoteRevisionSurplusRefundDueSourceReaderPort.php
- app/Ports/Out/Note/NoteRevisionSurplusRefundPaymentReaderPort.php
- app/Ports/Out/Note/NoteRevisionSurplusRefundPaymentWriterPort.php

New adapters:

- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusRefundDueSourceReaderAdapter.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusRefundPaymentAdapter.php

Changed provider binding:

- app/Providers/HexagonalServiceProvider.php

New tests:

- tests/Feature/Note/DatabaseNoteRevisionSurplusRefundPaymentAdapterTest.php
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest.php

This handoff also changes:

- docs/99_archive/handoff/v2/edit_refund_sniper/0015_refund_paid_backend_foundation_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md

## Behavior Implemented

DTO and port foundation:

- Surplus refund payment DTO.
- Locked refund_due source DTO.
- Command/result/result factory.
- Source reader port for locked active refund_due disposition.
- Refund payment reader and writer ports.

Adapter foundation:

- Source refund_due disposition read by disposition id with lockForUpdate.
- Active refund_paid sum by disposition.
- Active refund_paid lookup by disposition and idempotency key.
- Create refund payment row in note_revision_surplus_refund_payments.

Use case foundation:

- Admin-only actor guard.
- Required reason.
- Required idempotency key.
- Reject invalid or fully paid source refund_due.
- Reject amount greater than remaining refund_due.
- Check existing idempotency row inside transaction.
- Same disposition plus idempotency key plus same stored payload returns existing success.
- Same disposition plus idempotency key plus different stored payload is rejected.
- Create canonical audit_events and audit_event_snapshots.
- Create note_revision_surplus_refund_payments row.
- Run mutation through TransactionManagerPort transaction boundary.

Stored idempotency payload comparison in this slice:

- amount_rupiah
- effective_date

Reason comparison is not implemented because reason is stored in audit_events, not in note_revision_surplus_refund_payments.

## Behavior Explicitly Not Implemented

Not implemented:

- UI action
- route/controller
- report integration
- export integration
- cash ledger integration
- audit timeline display
- reversal/cancel
- customer_credit
- customer_balance_entries
- PostgreSQL
- Go API

Not reused or mutated:

- customer_refunds
- refund_component_allocations
- payment customer refund flow
- note refunded lifecycle
- inventory reversal

## Tests And Proof

DTO/ports syntax proof:

- No syntax errors detected in:
  - app/Application/Note/DTO/NoteRevisionSurplusRefundPayment.php
  - app/Application/Note/DTO/NoteRevisionSurplusRefundDueSource.php
  - app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentCommand.php
  - app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentResult.php
  - app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentResultFactory.php
  - app/Ports/Out/Note/NoteRevisionSurplusRefundDueSourceReaderPort.php
  - app/Ports/Out/Note/NoteRevisionSurplusRefundPaymentReaderPort.php
  - app/Ports/Out/Note/NoteRevisionSurplusRefundPaymentWriterPort.php

DTO/ports LOC proof:

- NoteRevisionSurplusRefundPayment.php: 93 lines
- NoteRevisionSurplusRefundDueSource.php: 65 lines
- RecordNoteRevisionSurplusRefundPaymentCommand.php: 25 lines
- RecordNoteRevisionSurplusRefundPaymentResult.php: 49 lines
- RecordNoteRevisionSurplusRefundPaymentResultFactory.php: 29 lines
- NoteRevisionSurplusRefundDueSourceReaderPort.php: 14 lines
- NoteRevisionSurplusRefundPaymentReaderPort.php: 17 lines
- NoteRevisionSurplusRefundPaymentWriterPort.php: 12 lines

Adapter syntax proof:

- No syntax errors detected in:
  - app/Adapters/Out/Note/DatabaseNoteRevisionSurplusRefundDueSourceReaderAdapter.php
  - app/Adapters/Out/Note/DatabaseNoteRevisionSurplusRefundPaymentAdapter.php
  - app/Providers/HexagonalServiceProvider.php
  - tests/Feature/Note/DatabaseNoteRevisionSurplusRefundPaymentAdapterTest.php

Adapter targeted proof:

    PASS Tests\Feature\Note\DatabaseNoteRevisionSurplusRefundPaymentAdapterTest
    Tests: 3 passed / 12 assertions

Adapter LOC proof:

- DatabaseNoteRevisionSurplusRefundDueSourceReaderAdapter.php: 52 lines
- DatabaseNoteRevisionSurplusRefundPaymentAdapter.php: 88 lines

Use case syntax proof:

- No syntax errors detected in:
  - app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentGuard.php
  - app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentFactory.php
  - app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentAuditEventFactory.php
  - app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentHandler.php
  - tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest.php

Use case targeted proof:

    PASS Tests\Feature\Note\RecordNoteRevisionSurplusRefundPaymentHandlerTest
    Tests: 5 passed / 24 assertions

Use case LOC proof:

- RecordNoteRevisionSurplusRefundPaymentGuard.php: 66 lines
- RecordNoteRevisionSurplusRefundPaymentFactory.php: 41 lines
- RecordNoteRevisionSurplusRefundPaymentAuditEventFactory.php: 66 lines
- RecordNoteRevisionSurplusRefundPaymentHandler.php: 97 lines

Focused backend blast-radius proof:

    PASS Tests\Feature\Note\DatabaseNoteRevisionSurplusRefundPaymentAdapterTest
    PASS Tests\Feature\Note\RecordNoteRevisionSurplusRefundPaymentHandlerTest
    PASS Tests\Feature\Note\DatabaseNoteRevisionSurplusDispositionAdapterTest
    PASS Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueHandlerTest
    PASS Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueControllerFeatureTest

    Tests: 22 passed / 97 assertions

Forbidden write flow source check:

- grep for note_revision_surplus_refund_payments in app/Application/Payment, app/Core/Payment, app/Application/Inventory, and app/Application/Note/Services produced no output.

Broader backend candidate lifecycle proof:

Candidate test found:

- tests/Unit/Application/Payment/Services/AllocateRefundAcrossComponentsTest.php

Result:

    PASS Tests\Unit\Application\Payment\Services\AllocateRefundAcrossComponentsTest
    Tests: 1 passed / 4 assertions

Refund_due plus refund_paid focused rerun proof:

    Tests: 22 passed / 97 assertions

Final syntax rerun for all new backend files and provider:

- all passed with no syntax errors.

## Residual Gaps

Still pending:

- full make verify
- full Note and Payment suite if owner wants wider proof before commit
- UI route/controller/form
- report integration
- export integration
- cash ledger integration
- audit timeline read/display
- reversal/cancel flow
- docs for UI/report/export labels
- final closure commit and push proof

## Residual Risks

Idempotency comparison currently checks stored payment payload only:

- amount_rupiah
- effective_date

It does not compare reason on repeated same idempotency request because reason is stored in audit_events, not in note_revision_surplus_refund_payments.

This is acceptable for backend foundation but should be revisited before exposing transport/UI if strict reason replay matching is required.

No true two-connection concurrent overpay stress test was run in this slice.

The lock path exists through lockForUpdate on the source refund_due disposition row, but database-level concurrency behavior is not stress-tested here.

## Next Active Step

Recommended next step:

Run a wider but still bounded backend verification before docs-only closure or commit:

- new refund_paid adapter test
- new refund_paid handler test
- existing refund_due adapter/handler/controller tests
- existing customer refund flow tests found by source grep
- optional broader Note and Payment feature/unit tests if runtime is acceptable

If owner accepts current proof as enough for backend foundation commit, owner may commit/push manually.

After commit/push proof, continue to one of these next slices:

1. Backend integration read model for refund_paid remaining amount and audit timeline.
2. Admin transport/UI submit path for refund_paid.
3. Report/cash ledger/export integration for surplus_refund_paid.

Do not start UI/report/export before backend proof is accepted.

## Next Session Opening Prompt

Kita lanjut HyperPOS refund_paid backend foundation dari handoff 0015.

Read first:

1. docs/01_standards/0001_index.md
2. docs/01_standards/0002_decision_policy.md
3. docs/99_archive/handoff/v2/edit_refund_sniper/README.md
4. docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
5. docs/99_archive/handoff/v2/edit_refund_sniper/0015_refund_paid_backend_foundation_handoff.md
6. docs/02_architecture/adr/0029_note_revision_surplus_refund_paid_execution.md

Locked decision:

refund_paid from refund_due uses note_revision_surplus_refund_payments.

Do not use customer_refunds.
Do not require customer_payment_id.
Do not create refund_component_allocations.
Do not trigger note refunded lifecycle.
Do not trigger inventory reversal.
Do not implement customer_credit.
Do not implement customer_balance_entries.
Do not implement PostgreSQL.
Do not implement Go API.
Do not start from UI/report/export unless backend proof is accepted.

Current implemented backend foundation:

- DTOs
- ports
- adapters
- DI bindings
- use case guard/factory/audit event factory/handler
- targeted adapter tests
- targeted use case tests

Latest proof:

- adapter targeted: 3 passed / 12 assertions
- use case targeted: 5 passed / 24 assertions
- focused refund_due plus refund_paid backend: 22 passed / 97 assertions
- customer refund allocator candidate: 1 passed / 4 assertions
- all new backend files syntax pass

Owner handles commit and push manually.

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

New latest handoff filename:

    0015_refund_paid_backend_foundation_handoff.md

## Session Context Health

76 percent.

Mini-summary required before continuing.

Reason:

- new backend foundation files
- provider binding changed
- tests added
- refund_paid remains finance-sensitive
- UI/report/export/cash ledger remain pending
