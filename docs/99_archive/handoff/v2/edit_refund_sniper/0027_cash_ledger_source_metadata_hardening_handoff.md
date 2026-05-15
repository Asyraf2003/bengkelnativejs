# Handoff 0027 - Cash Ledger Source Metadata Hardening

## Metadata

- Date: 2026-05-15
- Repo: HyperPOS Laravel
- Root: /home/asyraf/Code/laravel/bengkel2/app
- Branch: main
- Latest proven HEAD: cecef94b
- Latest proven remote: origin/main aligned with local HEAD
- Latest proven commit label: commit 2122
- Scope: edit/refund sniper, option 1 source path flow
- Owner workflow: owner handles commit, push, and manual sync
- Status: Fixed and locally verified

## Session Rule Lock

This session followed target-source option 1 first.

Locked sequencing decision:

1. Option 1 first: use one authoritative doc/debt/ADR/blueprint source as the target source.
2. Option 2 and option 3 are deferred.
3. Future target selection must stay one-by-one.
4. Do not mix multiple target sources in the same active step.

This sequencing must remain visible in the next session so the assistant does not restart broad repo analysis like a confused intern with a search bar.

## Starting Context

The active source path was selected from blueprint/ADR flow:

- docs/03_blueprints/finance/0006_note_revision_refund_ledger.md
- docs/02_architecture/adr/0029_note_revision_surplus_refund_paid_execution.md
- docs/02_architecture/adr/0030_note_revision_payment_settlement_and_cashier_calculator_contract.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0017_surplus_refund_paid_report_cash_ledger_read_model_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0018_surplus_refund_paid_report_screen_export_visibility_handoff.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0026_post_ai_pro_triage_context_trim_handoff.md

Previous AI Pro HP targets remained closed/session-safe and were not reopened:

- HP-UI-001
- HP-REFUND-001
- HP-INV-001
- HP-ROWS-001
- HP-REPORT-001
- HP-IDEMP-001

## Important Workflow Correction

During this session the assistant initially over-relied on local command output and underused GitHub repo reading.

Correction locked:

- Local command output remains highest source of truth for tests, make verify, dirty state, ignored files, runtime proof, and local-only state.
- Since owner stated local and GitHub are intentionally kept identical except ignored files, GitHub connector is acceptable for read-only source and docs inspection.
- Do not ask the owner to run terminal commands for source reads that can be inspected through GitHub.
- Still request local commands for tests, syntax checks, make verify, or local state proof.

## Target Selected

Target selected from option 1:

Cash ledger source metadata hardening with visible surface.

Classification:

Confirmed source-contract gap.

Reason:

- ADR 0029 requires surplus refund_paid from refund_due to use canonical table note_revision_surplus_refund_payments.
- ADR 0029 preserves source traceability through direct source links.
- Existing cash ledger read model included surplus_refund_paid as a cash-out event but did not preserve generic source metadata through the full reporting pipeline.
- Handoff 0018 explicitly left cash ledger source metadata hardening as a residual gap for source_table/source_id/source_disposition_id semantics.
- User decided source metadata should be displayed now because hiding later is easier than recovering missing display support later.

## Locked Non Goals

Do not implement in this slice:

- customer_credit
- customer_balance_entries
- PostgreSQL
- Go API
- dashboard
- reversal/cancel flow
- customer_refunds for surplus refund_paid
- refund_component_allocations for surplus refund_paid
- note refunded lifecycle trigger for surplus refund_paid
- inventory reversal for surplus refund_paid
- broad report rewrite
- broad repo audit

## Domain Decision

Every transaction cash ledger row must expose generic source metadata:

- source_table
- source_id
- source_disposition_id

Compatibility fields are preserved:

- customer_payment_id
- refund_id
- surplus_refund_payment_id where already used internally

Mapping decision:

1. payment_allocation
   - source_table: customer_payments
   - source_id: customer_payment_id
   - source_disposition_id: null

2. refund
   - source_table: customer_refunds
   - source_id: refund_id
   - source_disposition_id: null

3. surplus_refund_paid
   - source_table: note_revision_surplus_refund_payments
   - source_id: note_revision_surplus_refund_payments.id
   - source_disposition_id: note_revision_surplus_disposition_id

## Files Changed

Backend metadata chain:

- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerSurplusRefundPaidRowsQuery.php
- app/Ports/Out/Reporting/TransactionReportingSourceReaderPort.php
- app/Application/Reporting/DTO/TransactionCashLedgerPerNoteRow.php
- app/Application/Reporting/Services/TransactionCashLedgerPerNoteBuilder.php

DTO cleanup/extraction:

- app/Application/Reporting/DTO/Concerns/TransactionCashLedgerPerNoteRowSourceAccessors.php
- app/Application/Reporting/DTO/TransactionCashLedgerPerNoteRow.php

Surface visibility:

- resources/views/admin/reporting/transaction_cash_ledger/index.blade.php
- app/Application/Reporting/Exports/TransactionCashLedgerExcelDetailSheetWriter.php
- app/Application/Reporting/Exports/TransactionCashLedgerPdfViewDataBuilder.php
- resources/views/admin/reporting/transaction_cash_ledger/export_pdf.blade.php

Tests:

- tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
- tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php
- tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php
- tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php
- tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php

Follow-up make verify fix in commit 2122:

- app/Adapters/Out/Note/WorkItemDeletesTrait.php
- app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php
- app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php
- app/Ports/Out/Note/WorkItemWriterPort.php
- tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php

The commit 2122 changes were a follow-up fix based on system data to keep make verify green. Do not reinterpret commit 2122 as part of the cash ledger source metadata domain unless source proof later says so.

## RED Proof

### Backend metadata RED

Command:

php artisan test tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php

Result:

- 2 failed
- 2 passed
- 24 assertions

Failures:

- TransactionCashLedgerReportingQueryFeatureTest failed with Undefined array key "source_table".
- GetTransactionCashLedgerPerNoteFeatureTest failed because handler result did not include:
  - source_table
  - source_id
  - source_disposition_id

This proved source metadata was missing from query/handler output.

### Surface visibility RED

Command:

php artisan test tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php

Result:

- 3 failed
- 14 passed
- 98 assertions

Failures:

- TransactionCashLedgerPageFeatureTest failed because page did not contain Tabel Sumber.
- TransactionCashLedgerExcelExportFeatureTest failed because J1 was null instead of Tabel Sumber.
- TransactionCashLedgerPdfExportFeatureTest failed because PDF view did not contain Tabel Sumber.

This proved metadata was not visible on screen/export surfaces.

## GREEN Proof

### Backend metadata GREEN

Syntax passed for:

- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerSurplusRefundPaidRowsQuery.php
- app/Ports/Out/Reporting/TransactionReportingSourceReaderPort.php
- app/Application/Reporting/DTO/TransactionCashLedgerPerNoteRow.php
- app/Application/Reporting/Services/TransactionCashLedgerPerNoteBuilder.php

Command:

php artisan test tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php tests/Feature/Reporting/GetTransactionCashLedgerPerNoteFeatureTest.php

Result:

- 4 passed
- 29 assertions
- duration 5.92s

### Surface visibility GREEN

Syntax passed for:

- app/Application/Reporting/Exports/TransactionCashLedgerExcelDetailSheetWriter.php
- app/Application/Reporting/Exports/TransactionCashLedgerPdfViewDataBuilder.php
- resources/views/admin/reporting/transaction_cash_ledger/index.blade.php
- resources/views/admin/reporting/transaction_cash_ledger/export_pdf.blade.php

Command:

php artisan test tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php

Result:

- 17 passed
- 134 assertions
- duration 7.02s

### Full Verify

Command:

make verify

Result:

- 1056 passed
- 5712 assertions
- duration 45.89s

## Commit Proof

Latest git log proof from owner:

cecef94b (HEAD -> main, origin/main, origin/HEAD) commit 2122
- app/Adapters/Out/Note/WorkItemDeletesTrait.php
- app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php
- app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php
- app/Ports/Out/Note/WorkItemWriterPort.php
- tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php

3b924f0e commit 2121
- app/Application/Reporting/DTO/Concerns/TransactionCashLedgerPerNoteRowSourceAccessors.php
- app/Application/Reporting/DTO/TransactionCashLedgerPerNoteRow.php

9959a3b0 commit 2120
- app/Application/Reporting/Exports/TransactionCashLedgerExcelDetailSheetWriter.php
- app/Application/Reporting/Exports/TransactionCashLedgerPdfViewDataBuilder.php
- resources/views/admin/reporting/transaction_cash_ledger/export_pdf.blade.php
- resources/views/admin/reporting/transaction_cash_ledger/index.blade.php

63c47ec3 commit 2119
- tests/Feature/Reporting/TransactionCashLedgerPageFeatureTest.php
- tests/Feature/ReportingExports/TransactionCashLedgerExcelExportFeatureTest.php
- tests/Feature/ReportingExports/TransactionCashLedgerPdfExportFeatureTest.php

12b1fd86 commit 2118
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerRefundRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerSurplusRefundPaidRowsQuery.php
- app/Application/Reporting/DTO/TransactionCashLedgerPerNoteRow.php
- app/Application/Reporting/Services/TransactionCashLedgerPerNoteBuilder.php
- app/Ports/Out/Reporting/TransactionReportingSourceReaderPort.php

## Result

Cash ledger source metadata hardening is fixed and locally verified.

Visible surfaces now expose source metadata:

- admin cash ledger page
- Excel detail sheet
- PDF view/export

The slice is no longer just backend traceability. It is visible auditability.

## Residual Gaps

Known gaps not solved in this slice:

- No browser/manual QA.
- No reversal/cancel flow.
- No customer_credit.
- No customer_balance_entries.
- No PostgreSQL implementation.
- No Go API implementation.
- No dashboard work.
- Commit 2122 touched work item/payment allocation areas as a make verify follow-up; do not expand next scope from that without fresh target selection.

## Next Safe Step

Do not reopen option 1 unless new regression proof appears.

Next session should move to option 2 only after acknowledging:

- option 1 completed and verified;
- option 2 and 3 were intentionally deferred;
- target selection remains one-by-one.

Recommended next step:

Start option 2 target selection only.

Do not patch code before selecting the next valid target from proof.

## Opening Prompt For Next Session

Kita sedang di repo HyperPOS Laravel:

/home/asyraf/Code/laravel/bengkel2/app

Baca handoff ini dulu:

docs/99_archive/handoff/v2/edit_refund_sniper/0027_cash_ledger_source_metadata_hardening_handoff.md

Mode kerja wajib:

- Blueprint/proof-first.
- One active target per step.
- Jangan broad audit repo.
- GitHub repo boleh dipakai untuk read-only source/docs inspection karena local dan remote sengaja dijaga identik oleh owner.
- Local command output tetap source of truth tertinggi untuk test, make verify, dirty state, ignored files, dan runtime proof.
- Owner handles commit/push/manual sync.
- Jangan mulai dari git status/log/push/remote sync kecuali diminta.
- Jangan klaim selesai tanpa proof command lokal.
- Jangan patch sebelum target terbukti dari docs/source/test/output.

Locked completed target:

Cash ledger source metadata hardening with visible surface.

Proof:

- Backend RED: metadata missing, 2 failed / 2 passed / 24 assertions.
- Backend GREEN: 4 passed / 29 assertions.
- Surface RED: 3 failed / 14 passed / 98 assertions.
- Surface GREEN: 17 passed / 134 assertions.
- Full make verify: 1056 passed / 5712 assertions.
- Latest HEAD and origin/main: cecef94b commit 2122.

Sequencing decision:

- Option 1 has been completed first.
- Option 2 and option 3 are deferred.
- Continue with option 2 target selection only.
- Keep target selection one-by-one.

Do not reopen unless new regression proof appears:

- HP-UI-001
- HP-REFUND-001
- HP-INV-001
- HP-ROWS-001
- HP-REPORT-001
- HP-IDEMP-001
- Cash ledger source metadata hardening

Current task:

Start option 2 target selection only.

Expected response shape:

FACT
GAP
DECISION
NEXT
Proof/Progress
Session Context Health
