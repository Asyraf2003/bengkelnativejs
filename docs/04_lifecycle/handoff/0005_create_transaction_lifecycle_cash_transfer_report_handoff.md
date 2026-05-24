# Handoff - Create Transaction Lifecycle Cash Transfer Report

## Metadata
- Date: 2026-05-24
- Slice / topic: Create transaction lifecycle maturity proof, transfer naming, and cash-ledger reader alignment
- Workflow step: Phase 1F create lifecycle characterization toward edit/refund/report/audit readiness
- Status: continue in next session
- Progress: 48%

## Target Work Page
Continue lifecycle maturity proof for create transaction toward edit/revision, payment/refund, settlement, audit, and report readiness.

Current next target is consumer-level cash ledger/report exposure after the query reader was proven to read modern component allocation money-in and split cash vs transfer.

## References Used
- Blueprint: docs/03_blueprints/db/0015_create_edit_transaction_contract_matrix.md
- Blueprint: docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md
- Blueprint: docs/03_blueprints/db/0017_edit_refund_characterization_plan.md
- Blueprint: docs/03_blueprints/finance/0006_note_revision_refund_ledger.md
- ADR: docs/02_architecture/adr/0030_note_revision_payment_settlement_and_cashier_calculator_contract.md
- Blueprint: docs/03_blueprints/audit/0001_transactional_outbox_audit_runtime.md
- Previous handoff: docs/04_lifecycle/handoff/0004_refund_due_carry_forward_audit_fk_handoff.md
- Repo snapshot / command output: latest local operator outputs in the 2026-05-24 session

## Locked Facts
- Full make verify before this lifecycle sequence was GREEN:
  - Tests: 2 skipped, 1080 passed (5844 assertions)
  - Duration: 59.90s
- Audit FK/outbox mismatch for refund_due and refund_paid was already closed before this handoff.
- Global AuditEventWriterPort remains bound to DatabaseAuditOutboxWriterAdapter.
- FK-bound refund_due and refund_paid handlers use canonical DatabaseAuditEventWriterAdapter through contextual binding in InfrastructureServiceProvider.
- Audit FK blocker must not be reopened unless there is new RED proof.
- Create transaction without payment is a debt/save-note scenario.
- Transfer payment is real money-in and must be recorded and auditable separately from physical cash.
- Canonical customer payment money-in naming is transfer, not tf.
- Legacy tf input is normalized to transfer in the note payment path.

## Scope Used
### SCOPE-IN
- Create transaction service-only lifecycle characterization.
- Inline payment variants:
  - full cash
  - partial cash
  - no payment / debt / save note
  - full transfer
  - partial transfer
- Customer payment transfer naming canonicalization.
- Cash ledger query reader source-map, RED characterization, and narrow query patch.
- Report reader proof for component allocation money-in and cash vs transfer split.

### SCOPE-OUT
- Git status, git diff, git add, git commit, git push, branch, PR, merge.
- make verify as first action.
- UpdateTransactionWorkspaceHandler.
- Revision submit and payment submit merge.
- UI patch before source-map and consumer proof.
- Report patch that hides mismatch.
- PostgreSQL, Go API, dashboard performance, large seeder refactor.
- Expense/payment-out naming patch, except as a future separate selected step.
- Broad migration/backfill for legacy tf rows.

## GAP
- Cash ledger query reader is GREEN, but consumer layers may still collapse cash and transfer into total_cash_in_rupiah.
- app/Ports/Out/Reporting/TransactionReportingSourceReaderPort.php PHPDoc still documents only total_in_rupiah and total_out_rupiah for cash ledger reconciliation.
- app/Application/Reporting/Services/TransactionReportingReconciliationService.php still checks only total_in_rupiah and total_out_rupiah.
- app/Application/Reporting/UseCases/GetTransactionCashLedgerPerNoteHandler.php still builds expected reconciliation only with total_in_rupiah and total_out_rupiah.
- app/Application/Reporting/Services/TransactionCashLedgerSummaryBuilder.php currently maps total_cash_in_rupiah from totalIn only.
- app/Application/Reporting/Services/TransactionCashLedgerPeriodTableBuilder.php currently has cash_in_rupiah only.
- Transaction cash ledger view/export may still show only total cash-in and not transfer split.
- resources/views/cashier/notes/partials/payment-form.blade.php still has option value tf for Transfer.
- resources/views/admin/expenses/create.blade.php still has option value tf for expense payment method.
- Expense/payment-out is separate from create transaction money-in and must not be patched silently in this lifecycle step.
- Store-stock/inventory create lifecycle baseline has not been characterized yet.
- Rollback/idempotency characterization has not been done.
- Legacy database rows with payment_method tf do not yet have migration/backfill proof.

## Locked Decisions
- Progress tracking must be included in each technical response.
- Current progress is 48% lifecycle maturity proof.
- Progress meaning:
  - 20%: full/partial cash create lifecycle baseline proved.
  - 28%: no-payment debt/save-note baseline proved.
  - 35%: full transfer money-in baseline proved.
  - 40%: partial transfer money-in baseline proved.
  - 43%: canonical transfer naming for customer payment money-in proved.
  - 48%: cash ledger reader reads modern component allocation money-in and splits cash vs transfer.
- Use transfer as canonical customer payment money-in naming.
- Keep legacy tf normalization where needed.
- Do not patch UI/payment forms or expense naming until selected as a separate active step.
- Do not patch report consumer before exact consumer source-map and expected proof are stated.

## Files Created / Changed
### New files
- tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php
- docs/04_lifecycle/handoff/0005_create_transaction_lifecycle_cash_transfer_report_handoff.md

### Changed files
- app/Core/Payment/CustomerPayment/CustomerPaymentMethod.php
- app/Adapters/In/Http/Requests/Note/RecordNotePaymentInputNormalizer.php
- app/Adapters/In/Http/Requests/Note/RecordNotePaymentRequest.php
- tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
- app/Adapters/Out/Reporting/Queries/TransactionCashLedgerReportingQuery.php

## Verification Proof
- command:
  - php artisan test tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php
  - result:
    - Tests: 1 passed (19 assertions)
    - Duration: 6.09s
  - meaning:
    - Initial full cash create lifecycle baseline was proven.

- command:
  - php artisan test tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php tests/Feature/Payment/AutoClosePaidNoteOnFullPaymentFeatureTest.php tests/Feature/Payment/RecordCustomerPaymentFeatureTest.php
  - result:
    - Tests: 8 passed (50 assertions)
    - Duration: 6.23s
  - meaning:
    - Full cash baseline and adjacent payment/package/auto-close tests were GREEN.

- command:
  - php artisan test tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php tests/Feature/Payment/AutoClosePaidNoteOnFullPaymentFeatureTest.php tests/Feature/Payment/RecordCustomerPaymentFeatureTest.php
  - result:
    - Tests: 9 passed (69 assertions)
    - Duration: 6.56s
  - meaning:
    - Partial cash baseline was proven.

- command:
  - php artisan test tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php tests/Feature/Payment/AutoClosePaidNoteOnFullPaymentFeatureTest.php tests/Feature/Payment/RecordCustomerPaymentFeatureTest.php
  - result:
    - Tests: 10 passed (85 assertions)
    - Duration: 6.34s
  - meaning:
    - No-payment debt/save-note baseline was proven.

- command:
  - php artisan test tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php tests/Feature/Payment/AutoClosePaidNoteOnFullPaymentFeatureTest.php tests/Feature/Payment/RecordCustomerPaymentFeatureTest.php
  - result:
    - Tests: 11 passed (104 assertions)
    - Duration: 6.47s
  - meaning:
    - Full transfer payment lifecycle baseline was proven.

- command:
  - php artisan test tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php tests/Feature/Payment/AutoClosePaidNoteOnFullPaymentFeatureTest.php tests/Feature/Payment/RecordCustomerPaymentFeatureTest.php
  - result:
    - Tests: 12 passed (123 assertions)
    - Duration: 6.47s
  - meaning:
    - Partial transfer payment lifecycle baseline was proven.

- command:
  - php -l app/Core/Payment/CustomerPayment/CustomerPaymentMethod.php
  - php -l app/Adapters/In/Http/Requests/Note/RecordNotePaymentInputNormalizer.php
  - php -l app/Adapters/In/Http/Requests/Note/RecordNotePaymentRequest.php
  - php -l tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php
  - result:
    - No syntax errors detected in all four files.
  - meaning:
    - Transfer naming patch was syntactically valid.

- command:
  - php artisan test tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php tests/Feature/Note/CreateTransactionWorkspacePackageAllocationAuditFeatureTest.php tests/Feature/Payment/AutoClosePaidNoteOnFullPaymentFeatureTest.php tests/Feature/Payment/RecordCustomerPaymentFeatureTest.php
  - result:
    - Tests: 12 passed (123 assertions)
    - Duration: 6.48s
  - meaning:
    - Transfer canonical naming remained compatible with create/payment adjacent proof.

- command:
  - php artisan test tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php
  - result:
    - Tests: 1 failed, 8 passed (117 assertions)
    - Failure: actual row count 0, expected 2
  - meaning:
    - RED proof showed cash ledger did not read modern payment_component_allocations money-in.

- command:
  - php artisan test tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php --filter=component_allocation_money_in
  - result:
    - Tests: 1 failed (1 assertions)
    - Failure: actual row count 0, expected 2
  - meaning:
    - Targeted RED proof confirmed the cash ledger query gap.

- command:
  - php -l app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php
  - php -l app/Adapters/Out/Reporting/Queries/TransactionCashLedgerReportingQuery.php
  - result:
    - No syntax errors detected in both files.
  - meaning:
    - Cash ledger query patch was syntactically valid.

- command:
  - php artisan test tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php tests/Feature/Note/CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest.php
  - result:
    - Tests: 9 passed (133 assertions)
    - Duration: 6.36s
  - meaning:
    - Cash ledger query now reads component allocation money-in and splits cash vs transfer while create lifecycle remains GREEN.

## Source Map Notes
### Cash ledger consumer source-map command
- command:
  - rg "getTransactionCashLedgerPerNoteRows|getTransactionCashLedgerPerNoteReconciliation|TransactionCashLedgerReportingQuery|total_in_rupiah|total_out_rupiah" -n app resources tests
- findings:
  - app/Ports/Out/Reporting/TransactionReportingSourceReaderPort.php documents only total_in_rupiah and total_out_rupiah.
  - app/Application/Reporting/Services/TransactionReportingReconciliationService.php checks total_in_rupiah and total_out_rupiah only.
  - app/Application/Reporting/UseCases/DashboardCashLedgerTotals.php returns total_in_rupiah and total_out_rupiah.
  - app/Application/Reporting/UseCases/AdminDashboardOverviewPayload.php maps daily/monthly cash-in from total_in_rupiah.
  - app/Application/Reporting/UseCases/GetTransactionCashLedgerPerNoteHandler.php builds expected total_in_rupiah and total_out_rupiah only.
  - app/Adapters/Out/Reporting/DatabaseTransactionReportingSourceReaderAdapter.php delegates to TransactionCashLedgerReportingQuery.
  - tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php directly calls TransactionCashLedgerReportingQuery for surplus paid assertion.

### Cash/transfer exposure source-map command
- command:
  - rg "cash_in_rupiah|transfer_in_rupiah|payment_method" -n app/Application/Reporting app/Adapters/Out/Reporting resources/views tests/Feature/Reporting
- findings:
  - tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php asserts payment_method, cash_in_rupiah, and transfer_in_rupiah.
  - app/Adapters/Out/Reporting/Queries/TransactionCashLedgerPaymentRowsQuery.php now exposes payment_method.
  - app/Adapters/Out/Reporting/Queries/TransactionCashLedgerReportingQuery.php now returns cash_in_rupiah and transfer_in_rupiah.
  - app/Application/Reporting/Services/TransactionCashLedgerSummaryBuilder.php maps total_cash_in_rupiah from totalIn only.
  - app/Application/Reporting/Services/TransactionCashLedgerPeriodTableBuilder.php has cash_in_rupiah only.
  - resources/views/admin/reporting/transaction_cash_ledger/index.blade.php displays total_cash_in_rupiah only.
  - app/Application/Reporting/Exports/TransactionCashLedgerPdfViewDataBuilder.php labels Kas Masuk using total_cash_in_rupiah only.
  - app/Application/Reporting/Exports/TransactionCashLedgerExcelSummarySheetWriter.php labels Kas Masuk using total_cash_in_rupiah only.
  - app/Application/Reporting/Exports/TransactionCashLedgerExcelPeriodSheetWriter.php writes cash_in_rupiah only.
  - resources/views/cashier/notes/partials/payment-form.blade.php still uses option value tf for Transfer.
  - resources/views/admin/expenses/create.blade.php still uses option value tf for expense payment method.

## Risks / Follow-up Notes
- The cash ledger query now has new fields, but downstream summary/view/export may still collapse transfer into cash labels.
- Updating dashboard cash wording may be semantically bigger than cash ledger report only; treat dashboard separately unless selected.
- Existing operational profit report already uses cash_in_rupiah but transfer-in semantics are not yet proven.
- Existing expense payment method tf is a separate payment-out domain. Do not mutate it under customer payment money-in without its own source-map.
- Legacy database rows with tf need a later migration/backfill decision if production data exists.
- UI payment forms still using tf can create inconsistency in later manual payment route unless handled in a selected route/UI naming slice.

## Next Step
Phase 1F-9 - Cash ledger consumer characterization for cash-vs-transfer exposure.

Single active step:
- Source-map and patch the smallest consumer-level test for GetTransactionCashLedgerPerNoteHandler or TransactionCashLedgerSummaryBuilder.
- Goal: prove whether report consumer exposes cash and transfer separately after the query reader returns payment_method, cash_in_rupiah, and transfer_in_rupiah.
- Do not patch UI/export first.
- Do not patch expense naming.
- Do not ask for make verify as first action.

Recommended files to inspect first:
- app/Application/Reporting/UseCases/GetTransactionCashLedgerPerNoteHandler.php
- app/Application/Reporting/Services/TransactionCashLedgerSummaryBuilder.php
- app/Application/Reporting/Services/TransactionCashLedgerPeriodTableBuilder.php
- app/Application/Reporting/Exports/TransactionCashLedgerPdfViewDataBuilder.php
- app/Application/Reporting/Exports/TransactionCashLedgerExcelSummarySheetWriter.php
- app/Application/Reporting/Exports/TransactionCashLedgerExcelPeriodSheetWriter.php
- resources/views/admin/reporting/transaction_cash_ledger/index.blade.php
- app/Ports/Out/Reporting/TransactionReportingSourceReaderPort.php
- app/Application/Reporting/Services/TransactionReportingReconciliationService.php
- tests/Feature/Reporting related to transaction cash ledger page/export/handler
