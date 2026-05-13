# Handoff 0013 - Refund Due Report Audit And Refund Paid Gap

## Metadata

- Date: 2026-05-13
- Sequence: 0013
- Scope: refund_due UI enhancement, reporting visibility, export parity, audit timeline, and refund_paid contract gap
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0012_surplus_refund_due_blade_ui_handoff.md
- Status: targeted and focused proof passed for UI enhancement, report visibility, export parity, and audit timeline
- Owner workflow: owner handles commit and push manually

## Purpose

This handoff records the completed refund_due admin-operable follow-up slices after handoff 0012.

It also records the source-audited blocker for refund_paid execution.

The blocker is a contract gap, not a test failure.

Do not implement refund_paid by reusing the existing customer refund flow blindly.

## Completed In This Session

### 1. Safe-state closure for handoff 0012

Owner stated:

    aman semua

Per SESSION_CONTRACT, owner statements such as clean, pushed, latest, or make verify pass are accepted as FACT.

Therefore handoff 0012 was treated as operationally safe.

### 2. Refund Due progressive enhancement

Files touched:

- public/assets/static/js/pages/note-surplus-refund-due.js
- resources/views/shared/notes/partials/payment-summary-actions.blade.php
- resources/views/shared/notes/show.blade.php
- tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php

Behavior added:

- native JS submit lock for refund_due form
- loading text on submit
- client-side clamp against backend-provided max unresolved pending amount
- fallback POST remains intact
- server remains financial source of truth

Proof:

    PASS Tests\Feature\Note\AdminNoteSurplusRefundDueUiFeatureTest
    Tests: 2 passed / 22 assertions

    PASS focused UI and controller adjacency
    Tests: 10 passed / 74 assertions

### 3. Transaction report refund_due visibility

Files touched:

- app/Adapters/Out/Reporting/Queries/TransactionSummaryRefundDueTotalsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php
- app/Ports/Out/Reporting/TransactionReportingSourceReaderPort.php
- app/Application/Reporting/DTO/TransactionSummaryPerNoteRow.php
- app/Application/Reporting/Services/TransactionSummaryPerNoteBuilder.php
- app/Application/Reporting/Services/TransactionReportSummaryBuilder.php
- app/Application/Reporting/Services/TransactionPeriodBreakdownBuilder.php
- app/Application/Reporting/Services/TransactionCustomerBreakdownBuilder.php
- resources/views/admin/reporting/transaction_summary/index.blade.php
- tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php
- tests/Feature/Reporting/RefundedNoteCashReportingFallbackFeatureTest.php

Behavior added:

- transaction summary dataset includes refund_due_rupiah
- summary includes refund_due_rupiah
- period breakdown includes refund_due_rupiah
- customer breakdown includes refund_due_rupiah
- transaction report page shows Refund Due
- refund_due is not counted as refunded_rupiah
- refund_due is not deducted from net cash
- transaction cash ledger remains untouched because refund_due is not cash out

Proof:

    PASS Tests\Feature\Reporting\GetTransactionReportDatasetFeatureTest
    Tests: 1 passed / 16 assertions

    PASS focused report and refund_due adjacency
    Tests: 7 passed / 48 assertions

### 4. Transaction report export parity

Files touched:

- app/Application/Reporting/Exports/TransactionReportExcelSummarySheetWriter.php
- app/Application/Reporting/Exports/TransactionReportExcelDetailSheetWriter.php
- app/Application/Reporting/Exports/TransactionReportExcelPeriodSheetWriter.php
- app/Application/Reporting/Exports/TransactionReportExcelCustomerSheetWriter.php
- app/Application/Reporting/Exports/TransactionReportPdfViewDataBuilder.php
- resources/views/admin/reporting/transaction_summary/export_pdf.blade.php
- tests/Unit/Application/Reporting/Exports/TransactionReportExportRefundDueVisibilityTest.php

Behavior added:

- Excel summary sheet includes Total Refund Due
- Excel detail sheet includes Refund Due
- Excel period sheet includes Refund Due
- Excel customer sheet includes Refund Due
- PDF view-data builder now uses current transaction summary dataset keys
- PDF view-data includes Refund Due
- PDF view includes Refund Due column

Proof:

    PASS Tests\Unit\Application\Reporting\Exports\TransactionReportExportRefundDueVisibilityTest
    Tests: 2 passed / 19 assertions

    PASS focused export, report, refund_due adjacency
    Tests: 9 passed / 67 assertions

### 5. Refund Due read-only audit timeline

Files touched:

- app/Ports/Out/Note/NoteSurplusDispositionAuditTimelineReaderPort.php
- app/Adapters/Out/Note/DatabaseNoteSurplusDispositionAuditTimelineReaderAdapter.php
- app/Application/Note/Services/NoteSurplusDispositionAuditTimelineBuilder.php
- app/Application/Note/Services/NoteDetailPageDataBuilder.php
- app/Application/Note/Services/NoteDetailNotePayloadBuilder.php
- app/Providers/HexagonalServiceProvider.php
- resources/views/shared/notes/partials/payment-summary-actions.blade.php
- tests/Feature/Note/AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest.php

Behavior added:

- note detail payload includes surplus_disposition_audit_timeline
- read model queries note_revision_surplus_dispositions joined to audit_events
- filter event_name = note_revision_surplus_refund_due_created
- admin note detail renders Timeline Audit Surplus
- UI shows Refund Due Ditandai, amount, after pending, reason, occurred_at, actor role
- read-only only
- no audit_logs dependency
- no refund_paid implication

Proof:

    PASS Tests\Feature\Note\AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest
    Tests: 2 passed / 16 assertions

    PASS focused audit timeline, UI, controller adjacency
    Tests: 9 passed / 63 assertions

## Locked Decisions Preserved

- refund_due is a surplus disposition decision.
- refund_due is not refund_paid.
- refund_due does not mean money already left the business.
- refund_due is not customer_credit.
- customer_credit remains blocked until customer identity is locked.
- customer_balance_entries remains out of scope.
- PostgreSQL implementation remains out of scope.
- Go API implementation remains out of scope.
- audit_events and audit_event_snapshots are canonical for new finance-sensitive audit.
- UI is not financial truth.
- Report query must distinguish refund_due from refund_paid/refunded cash out.
- Cash ledger must not include refund_due because no money left the business yet.

## Refund Paid Source Audit

Source audit before implementation found a blocker.

Existing customer_refunds table columns:

- id
- customer_payment_id
- note_id
- amount_rupiah
- refunded_at
- reason

Missing for refund_paid from refund_due:

- note_revision_surplus_disposition_id
- note_revision_settlement_id
- audit_event_id
- status or lifecycle source marker
- explicit surplus disposition source id

Existing DatabaseCustomerRefundWriterAdapter inserts only:

- id
- customer_payment_id
- note_id
- amount_rupiah
- refunded_at
- reason

Existing RecordCustomerRefundOperation requires:

- customerPaymentId
- noteId
- amountRupiah
- refundedAt
- reason
- selectedRowIds optional

It validates against allocated payment and existing refunded amount by customer payment plus note pair.

It then allocates refund across components.

Existing RecordCustomerRefundTransaction also:

- calls AutoReverseRefundedStoreStockInventory
- calls AutoRefundNoteWhenFullyRefunded
- writes legacy AuditLogPort event customer_refund_recorded
- syncs note projection

## Refund Paid Contract Gap

refund_paid from refund_due cannot safely reuse the existing refund operation directly because:

1. refund_due surplus disposition does not expose customer_payment_id.
2. refund_due is a liability disposition, not necessarily a component refund.
3. Existing refund operation allocates against payment allocations and refund components.
4. Existing refund operation may trigger note refunded lifecycle and inventory reversal.
5. Existing refund operation writes audit_logs, while this chain requires canonical audit_events.
6. customer_refunds lacks source link to note_revision_surplus_dispositions.
7. customer_refunds lacks audit_event_id.
8. There is no locked rule whether refund_paid should extend customer_refunds, use a new table, or create a bridge table.

## Technical Debt Recorded

Technical debt item:

    refund_paid execution contract is blocked until source linkage is locked.

Minimum decision needed before production patch:

- whether refund_paid writes into customer_refunds or a new table
- whether customer_refunds gets nullable source columns or an immutable bridge table
- how to identify source customer_payment_id, if required
- whether refund_paid from surplus should create refund_component_allocations
- whether refund_paid from surplus may trigger note refunded lifecycle
- whether refund_paid may trigger inventory reversal
- required canonical audit_events event name and snapshots
- idempotency policy for repeated refund_paid submit
- concurrency policy for multiple admins executing refund_paid from same refund_due

## Recommended Next Safe Step

Do not implement refund_paid execution yet.

Next safe step:

    Create ADR or blueprint for refund_paid execution contract from refund_due.

Minimum design options to compare:

1. Extend customer_refunds with source columns.
2. Create note_revision_surplus_refund_payments table.
3. Create bridge table between note_revision_surplus_dispositions and customer_refunds.

Evaluation criteria:

- source traceability
- canonical audit_events compatibility
- reporting clarity
- migration risk
- existing refund/inventory lifecycle risk
- future PostgreSQL/API readiness
- rollback/reversal readiness

## Progress Snapshot

Final Goal Progress:

- 79 percent for refund_due admin-operable chain.
- Reason: refund_due backend, admin transport, data exposure, Blade action, JS enhancement, report visibility, Excel/PDF export parity, and audit timeline are targeted/focused green.
- Remaining: refund_paid contract design/execution, cancellation/reversal, idempotency, concurrency hardening, browser/manual QA, final global safe-state.

Main Process Progress:

- 98 percent for refund_due-only admin-operable chain before refund_paid.
- Reason: refund_due is operable and visible, but actual money-out execution remains intentionally blocked.

Sub-step Progress:

- 100 percent for audit timeline read-only slice.
- Proof:
  - targeted audit timeline 2 passed / 16 assertions
  - focused audit timeline + UI + controller 9 passed / 63 assertions

## Session Context Health

68 percent.

Caution.

Mini-summary:

- Locked facts: refund_due-only completed through UI/report/export/audit timeline, refund_due is not refund_paid.
- Current active blocker: refund_paid execution contract gap.
- Latest proof: audit timeline targeted 2/16, focused 9/63.
- Next safest step: ADR/blueprint for refund_paid execution contract, not production mutation.
