# Refund Due Carry-Forward and Audit FK Handoff

## Metadata

- Date: 2026-05-24
- Repo: Asyraf2003/hyperpos
- Slice / topic: note revision refund_due carry-forward, audit FK/outbox mismatch, create/edit/refund maturity
- Workflow step: continuation after Phase 1D refund_due characterization and narrow implementation
- Status: continue in next session
- Current active blocker: audit FK mismatch in refund_due/refund_paid HTTP and handler flows

## Target Work Page

Target domain:

- create transaction maturity
- edit transaction / note revision maturity
- ordinary refund
- surplus refund_due
- surplus refund_paid
- revision settlement carry-forward
- audit records for every important action
- audit runtime that feels watched but remains fast through outbox/runtime separation
- seeders that are mature enough to support realistic edit/refund/report proofs

Core product goal:

Make create transaction mature enough that edit and refund workflows are safe, auditable, fast, and reportable.

Core audit UX goal:

Every meaningful action should feel monitored and traceable, but user-facing actions should remain instant and should not feel like heavy telemetry blocks the request.

Core data goal:

Seeder and fixture support must be mature enough to prove realistic create/edit/refund/report flows without guessing schema columns.

## Rules / Guardrails

- Read repo rules first before continuing:
  - docs/04_lifecycle/handoff/README.md
  - docs/01_standards/0005_handoff_template.md
  - docs/01_standards/core/0010_scope_and_facts.md
  - docs/01_standards/core/0011_blueprint_first.md
  - docs/01_standards/core/0012_step_by_step_execution.md
  - docs/01_standards/core/0013_proof_and_progress.md
  - docs/01_standards/workflow/0020_response_structure.md
  - docs/01_standards/workflow/0021_active_step_policy.md
  - docs/01_standards/output/0033_terminal_command_delivery.md
- Do not mix assumption with fact.
- Separate FACT, GAP, ASSUMPTION, DECISION, PROOF, NEXT.
- Do not patch UI first.
- Do not patch reports to hide domain mismatch.
- Do not patch UpdateTransactionWorkspaceHandler unless route binding proof or dead-path decision exists.
- Do not merge revision submit and payment submit.
- Do not guess columns. Source-map migrations/tests first.
- Git operations are operator-owned.

## Source Anchors

Relevant docs:

- docs/03_blueprints/db/0015_create_edit_transaction_contract_matrix.md
- docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md
- docs/03_blueprints/db/0017_edit_refund_characterization_plan.md
- docs/03_blueprints/finance/0006_note_revision_refund_ledger.md
- docs/02_architecture/adr/0030_note_revision_payment_settlement_and_cashier_calculator_contract.md
- docs/03_blueprints/audit/0001_transactional_outbox_audit_runtime.md

Relevant implementation:

- app/Application/Note/Services/BuildCreateNoteRevisionSettlement.php
- app/Ports/Out/Note/NoteRevisionSurplusDispositionReaderPort.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusDispositionAdapter.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusRefundPaymentAdapter.php
- app/Application/Note/UseCases/CreateNoteRevisionSurplusRefundDueHandler.php
- app/Application/Note/UseCases/RecordNoteRevisionSurplusRefundPaymentHandler.php
- app/Providers/InfrastructureServiceProvider.php
- app/Adapters/Out/Audit/DatabaseAuditOutboxWriterAdapter.php
- app/Adapters/Out/Audit/DatabaseAuditEventWriterAdapter.php

Relevant tests:

- tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php
- tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php
- tests/Unit/Application/Note/Services/BuildCreateNoteRevisionSettlementTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php
- tests/Feature/Note/NoteReplacementOverpaidAllocationReplayFeatureTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueRaceInvariantTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest.php

## FACT

Phase 1D-1 settlement carry-forward was implemented and verified locally.

Targeted test added:

- tests/Feature/Note/NoteRevisionSettlementCarryForwardFeatureTest.php

It covers:

- revision after partial payment carries paid amount into underpaid settlement
- revision after ordinary refund counts refund once in settlement

Operator proof:

- targeted test passed with 2 tests and 4 assertions
- adjacency proof passed with 9 tests and 40 assertions

Next characterization test added:

- tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php

It proved RED first:

- expected carry_forward_refunded_rupiah = 122000
- actual carry_forward_refunded_rupiah = 0
- actual settlement incorrectly became overpaid_pending

Narrow production patch was applied:

- extended NoteRevisionSurplusDispositionReaderPort with sumActiveRefundDueAmountByNoteRootId
- implemented sumActiveRefundDueAmountByNoteRootId in DatabaseNoteRevisionSurplusDispositionAdapter
- injected NoteRevisionSurplusDispositionReaderPort into BuildCreateNoteRevisionSettlement
- settlement builder now treats refund_due/refund_paid as unavailable surplus obligation effect
- unit builder tests were expanded

Current formula intent:

- ordinary refunded reduces carry-forward net exactly once
- surplus refund_paid is cash-out and must not be reclaimed
- refund_due is liability and must not be silently consumed
- surplus obligation effect uses max(active refund_due, active surplus refund_paid) to avoid double count when both records exist for the same surplus obligation

Operator proof after patch:

- php artisan test tests/Feature/Note/NoteRevisionRefundDueCarryForwardFeatureTest.php
  - PASS
  - 1 passed
  - 3 assertions
- php artisan test tests/Unit/Application/Note/Services/BuildCreateNoteRevisionSettlementTest.php
  - PASS
  - 6 passed
  - 34 assertions
- settlement blast-radius suite:
  - PASS
  - 12 passed
  - 55 assertions

PHPStan blocker fixed/identified:

- make verify first failed because LockAwarePendingRefundDueReaderFake did not implement new sumActiveRefundDueAmountByNoteRootId method.
- This is a test fake contract drift from extending NoteRevisionSurplusDispositionReaderPort.

Remaining full verify blocker from operator log:

- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php returns HTTP 500.
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest also has QueryException.
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest has HTTP 500.
- tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest has QueryException.
- Final make verify summary from operator log:
  - 10 failed
  - 2 skipped
  - 1070 passed
  - 5781 assertions

Common failure shape:

- SQLSTATE[23000]
- FK violation
- note_revision_surplus_dispositions.audit_event_id references audit_events.id
- note_revision_surplus_refund_payments.audit_event_id references audit_events.id
- inserted domain row refers to audit-event-refund-due-http-001 or generated refund-paid audit event id
- corresponding audit_events row does not exist at insert time

Current audit binding fact:

- InfrastructureServiceProvider binds AuditEventWriterPort to DatabaseAuditOutboxWriterAdapter.
- DatabaseAuditOutboxWriterAdapter writes audit_outbox only.
- DatabaseAuditEventWriterAdapter writes audit_events and audit_event_snapshots.
- Audit outbox blueprint says outbox runtime is intended for fast user-facing action plus later canonical audit materialization.
- However, refund_due/refund_paid domain tables still enforce hard FK to audit_events.

## GAP

- Full make verify is not GREEN.
- Current outbox audit runtime is incompatible with domain tables that require immediate audit_events FK.
- No source-map has yet proven the correct fix strategy for refund_due/refund_paid:
  - direct canonical audit writer for these FK-bound flows,
  - hybrid writer that inserts canonical event and outbox,
  - schema change to reference outbox or nullable audit_event_id,
  - or processor-before-domain-write pattern.
- No decision yet whether FK-bound transaction/refund flows are excluded from global outbox binding.
- Seeder maturity for realistic create/edit/refund/report flow is still incomplete.
- Report/export proof after edit/refund/refund_due/refund_paid is not complete.
- Audit monitoring UI/status for pending/failed outbox is not complete.
- Full audit action coverage matrix is not complete.
- Full transaction create -> edit -> refund -> report end-to-end proof is not complete.

## ASSUMPTION

Do not assume audit_outbox rows satisfy existing audit_events FK.

Do not assume the audit processor runs inside the same HTTP request.

Do not assume refund_due/refund_paid can keep hard audit_events FK while using outbox-only AuditEventWriterPort unless source proves canonical audit row is materialized before domain insert.

Do not assume this failure was introduced only by refund_due carry-forward. The carry-forward work surfaced and crossed paths with existing audit runtime migration.

## DECISION

Current state must be blocked from release/full-verify success until audit FK mismatch is fixed or explicitly scoped out.

Do not ignore the 10 failing tests.

Do not hide this in report patches.

Do not weaken audit FK without ADR/blueprint decision.

Next active step should be source-map and narrow patch for audit FK/outbox mismatch in refund_due/refund_paid flows.

Preferred next slice name:

Phase 1E-1 - Audit FK compatibility for refund_due/refund_paid outbox runtime.

## PROOF

Targeted settlement proof:

- NoteRevisionRefundDueCarryForwardFeatureTest: PASS, 1 passed, 3 assertions.
- BuildCreateNoteRevisionSettlementTest: PASS, 6 passed, 34 assertions.
- Settlement blast-radius suite: PASS, 12 passed, 55 assertions.

Full verify blocker proof from operator log:

- make verify failed after tests.
- 10 failed, 2 skipped, 1070 passed, 5781 assertions.
- Common failure: SQLSTATE[23000] FK violation from audit_event_id referencing missing audit_events row.

## NEXT

One active step only:

Source-map audit writer and FK-bound refund_due/refund_paid flows before patching.

Recommended commands:

grep -R "AuditEventWriterPort::class" -n app tests
grep -R "DatabaseAuditOutboxWriterAdapter" -n app tests docs
grep -R "DatabaseAuditEventWriterAdapter" -n app tests docs
grep -R "audit_event_id" -n database/migrations app/Application app/Adapters tests/Feature/Note
grep -R "note_revision_surplus_dispositions" -n database/migrations app tests/Feature/Note
grep -R "note_revision_surplus_refund_payments" -n database/migrations app tests/Feature/Note

Targeted failing proof commands:

php artisan test tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php
php artisan test tests/Feature/Note/CreateNoteRevisionSurplusRefundDueHandlerTest.php
php artisan test tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest.php
php artisan test tests/Feature/Note/RecordNoteRevisionSurplusRefundPaymentHandlerTest.php

Decision checkpoint after source-map:

Option A:
Use direct canonical DatabaseAuditEventWriterAdapter for FK-bound refund_due/refund_paid flows until outbox schema is made FK-compatible.

Option B:
Introduce composite/hybrid audit writer for FK-bound flows that writes canonical audit_events synchronously and optionally outbox for async processing.

Option C:
Change domain schema away from audit_events FK, but only with ADR and migration plan.

Do not choose before source-map proof.

