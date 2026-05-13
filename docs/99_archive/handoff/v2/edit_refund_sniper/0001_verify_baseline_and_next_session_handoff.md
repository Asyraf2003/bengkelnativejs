# Handoff 0001 - Edit Refund Sniper Baseline And Next Session

## Metadata

- Date: 2026-05-13
- Sequence: 0001
- Scope: verify baseline and next session handoff

## Status

Prepared for next AI session.

This handoff is a session-chain entrypoint, not an implementation patch.

## Local Baseline From User Output

Branch:

    main

HEAD:

    6425a8b7

Latest log from user output:

    6425a8b7 (HEAD -> main, origin/main, origin/HEAD) commit 1927
    e17e8bf7 commit 1926
    75304eea commit 1925
    f219484b commit 1924
    a5c29919 commit 1923

Verification before compatibility-doc fix:

    phpstan: OK, no errors
    audit-lines: OK
    audit-blade: OK
    contract audit: OK
    pest: 3 failed, 957 passed, 5106 assertions

Failure cause:

    Tests\Feature\Reporting\ReportingReadModelContractFeatureTest expected legacy docs paths:
    docs/02_architecture/adr/0009-reporting-as-read-model.md
    docs/03_blueprints/workflow_v1.md

Current available source docs found:

    docs/02_architecture/adr/0009_reporting_as_read_model.md
    docs/99_archive/blueprints/0002_workflow_v1.md

Decision taken:

    Create compatibility docs at the paths expected by the contract test.
    Do not weaken the test.
    Do not patch reporting logic.

Patch command used in prior session:

    mkdir -p docs/02_architecture/adr docs/03_blueprints
    cp docs/02_architecture/adr/0009_reporting_as_read_model.md docs/02_architecture/adr/0009-reporting-as-read-model.md
    cp docs/99_archive/blueprints/0002_workflow_v1.md docs/03_blueprints/workflow_v1.md
    php artisan test tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php
    make verify

User reported:

    ok passed

Proof quality note:

    User reported verification passed, but exact final full passing output was not pasted in this chat.
    Next session should request or run local proof if it needs exact final command output.

Expected changed files from verify compatibility fix:

    docs/02_architecture/adr/0009-reporting-as-read-model.md
    docs/03_blueprints/workflow_v1.md

Do not assume additional changed files without git status --short --untracked-files=all.

## Markdown Safety Correction

This handoff replaces the previous unsafe Markdown delivery pattern.

Rule for future AI:

    Do not place fenced Markdown blocks inside a heredoc that is itself shown inside a fenced chat block.
    Do not use triple backtick in handoff, prompt, or Markdown file delivery.
    Prefer no fenced blocks inside handoff files.
    Use indented command blocks instead.
    Before finalizing a handoff folder, grep for code fences and require no output.

Required proof:

    Use a scanner that builds fence tokens from character codes instead of storing literal fence tokens in this file.

Expected:

    no output

Reason:

    Nested fences corrupt rendered chat output and can corrupt copied Markdown handoff.
    This is a delivery correctness issue, not cosmetic formatting.

## Session Goal

The next session should continue the edit/refund/revision ledger initiative with a lighter entrypoint.

The goal is not to re-read every old handoff.

The goal is:

1. Confirm local baseline.
2. Read this folder first.
3. Read only canonical docs linked here.
4. Audit specific source files for the selected active slice.
5. Produce a minimal blueprint before any production code edit.
6. Patch only after proof and decision lock.

## Locked Working Style

Use strict response sections:

- FACT
- GAP
- ASSUMPTION
- DECISION
- ACTIVE STEP
- FILES TO TOUCH
- FILES NOT TO TOUCH
- COMMAND
- EXPECTED PROOF
- NEXT

Rules:

- One active step per response.
- No silent assumptions.
- No progress without proof.
- Local command output beats docs, remote, and memory.
- If scope is unsafe, stop at GAP.
- If a decision is not locked, ask for minimum owner decision or stop before implementation.
- User handles commit and push manually unless explicitly asked.

## Core Domain Direction

The active domain chain is:

- note edit
- note revision
- refund
- settlement carry-forward
- overpaid / kembalian / refund due / customer credit
- inventory reversal / stock return
- current projection
- report version mode
- UI preview / commit
- future API parity
- audit

Locked architecture direction from latest blueprint:

    Ledger + Revision Snapshot + Current Projection

Meaning:

- note revisions are immutable business snapshots
- payment and refund records are financial ledger events
- inventory movements are stock ledger events
- current note history projection is fast read model
- work items are current operational rows or active projection, not final historical truth
- customer balance entries are required for surplus/refund due/customer credit
- UI and API are transport adapters only
- domain policy must not live in Blade, JavaScript, controller, or raw SQL branches

## Mandatory Docs For Next Session

Read in this order:

1. docs/01_standards/0001_index.md
2. docs/01_standards/0002_decision_policy.md
3. docs/99_archive/handoff/v2/edit_refund_sniper/README.md
4. docs/99_archive/handoff/v2/edit_refund_sniper/0001_verify_baseline_and_next_session_handoff.md
5. docs/99_archive/handoff/v2/note_finance/0003_note_revision_refund_ledger_ai_reading_map.md
6. docs/03_blueprints/finance/0006_note_revision_refund_ledger.md
7. docs/03_blueprints/finance/0007_note_revision_refund_ledger_dod.md
8. docs/03_blueprints/finance/0008_note_revision_refund_ledger_workflow.md
9. docs/02_architecture/adr/0018_note_revision_settlement_external_product_lifecycle.md
10. docs/02_architecture/adr/0024_note_current_projection_and_current_only_refund.md
11. docs/02_architecture/adr/0025_note_revision_carry_forward_settlement.md
12. docs/02_architecture/adr/0022_payment_allocation_concurrency_and_over_allocation_protection.md
13. docs/02_architecture/adr/0019_note_access_boundary_cashier_date_window_and_transaction_capability_enforcement.md

## Source Priority

Use:

1. Local command output
2. Current source code
3. Latest ADR/blueprint nearest active domain
4. Error log with proof
5. Latest handoff in this folder
6. Older handoff/archive
7. Memory or assumption

If docs say fixed but source contradicts it, source wins.

If user command output contradicts remote GitHub, user command output wins.

## Important Existing Findings And Constraints

### Reporting compatibility blocker

A verification blocker was found and fixed by compatibility docs.

This was not a domain logic failure.

Do not reopen reporting read model behavior unless new proof shows a reporting domain defect.

### Error log 004 boundary

docs/04_lifecycle/error_log/0004_refunded_work_items_survive_revisions_and_inflate_stock.md was previously fixed for tested Note/Payment/current operational flow.

Known limitation from the doc:

    Reporting queries that read work_items directly were not part of that verification slice.
    They remain a separate audit target if reporting mismatch is suspected.

Do not reopen 004 without new proof.

### Error log 005 boundary

docs/04_lifecycle/error_log/0005_note_revision_silently_drops_overpaid_allocations.md was previously fixed for safe behavior.

Current safe behavior:

    downward revision with old allocated payment greater than revised payable components rejects and rolls back

Known future product gap:

    explicit overpaid/customer credit/refund due model is still out of scope

The next major initiative should not reintroduce silent cap behavior.

### Current projection direction

Current note UI and current mutation flows must use current projection/current active state.

Legacy, canceled, replaced, refunded, or historical rows must not re-enter current payment/refund/edit/inventory flows unless an explicit correction/reversal policy allows it.

### Refund direction

Refund is not money-back only.

Refund/reversal can involve:

- money refund
- stock return
- receivable cancellation
- service cancellation
- external procurement effect
- customer balance effect
- projection update
- audit event

Money effect and stock effect must be separated.

### Concurrency direction

Same-note finance mutations must serialize through note-level transaction protocol where relevant.

No UI debounce, disabled button, modal, or cashier instruction counts as concurrency protection.

Hope is not a lock primitive.

## Recommended First Active Slice

Recommended first serious implementation slice:

    Revision Settlement and Customer Balance Foundation

Reason:

- downward revision / overpaid is the core blocker
- refund and reporting depend on settlement and customer balance clarity
- UI can only render honest state after backend can produce a settlement/refund plan
- future API must share the same application use cases

Minimum first slice output should be decided after source audit.

Potential concepts from blueprint:

- note_revision_settlements
- customer_balance_entries
- revision settlement DTO/core model
- writer and reader ports
- adapter
- settlement builder
- tests for equal, upward, downward revision

Do not add all tables blindly.

Only add DB concepts after the active slice proves it needs them.

## Required Local Baseline Before Any Patch

Run:

    git status --short --untracked-files=all
    git rev-parse --abbrev-ref HEAD
    git rev-parse --short HEAD
    git log --oneline -5
    git diff --stat

If exact verify status is needed:

    make verify

## Required Source Audit Before Patch

Use the reading map first, then inspect only active-slice files.

For revision/edit:

- database/migrations/2026_04_22_000001_create_note_revisions_table.php
- database/migrations/2026_04_22_000002_create_note_revision_lines_table.php
- database/migrations/2026_04_22_000003_add_current_revision_pointer_to_notes_table.php
- app/Core/Note/Revision/NoteRevision.php
- app/Core/Note/Revision/NoteRevisionLineSnapshot.php
- app/Adapters/Out/Note/DbNoteRevisionPayloadCodec.php
- app/Adapters/Out/Note/DbNoteRevisionRowMapper.php
- app/Adapters/Out/Note/DbNoteRevisionLineRowMapper.php
- app/Adapters/Out/Note/Concerns/WritesNoteRevisionRecords.php
- app/Adapters/Out/Note/Concerns/QueriesNoteRevisionRecords.php
- app/Application/Note/UseCases/CreateNoteRevisionHandler.php
- app/Application/Note/UseCases/CreateNoteRevisionWorkflow.php
- app/Application/Note/UseCases/CreateNoteRevisionCommitter.php
- app/Application/Note/UseCases/CreateNoteRevisionAuditPayloadBuilder.php
- app/Application/Note/Services/NoteCurrentRevisionResolver.php
- app/Application/Note/Services/EditTransactionWorkspacePageDataBuilder.php
- app/Application/Note/Services/NoteRevisionWorkspaceExistingItemMapper.php

Dangerous delete/rebuild/payment replay files:

- app/Adapters/Out/Note/WorkItemDeletesTrait.php
- app/Adapters/Out/Payment/DatabasePaymentComponentAllocationWriterAdapter.php
- app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php
- app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php
- app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php
- app/Application/Note/Services/CancelSelectedRowsAndSyncActiveNoteTotal.php
- app/Application/Payment/Services/AllocatePaymentAcrossComponents.php

Refund files if refund is touched:

- app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php
- app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php
- app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php
- app/Application/Note/Services/SelectedNoteRowsRefundEligibilityGuard.php
- app/Application/Note/Services/SelectedRowsRefundBucketsBuilder.php
- app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php
- app/Application/Payment/Services/RecordSelectedRowsRefundPlanBucketProcessor.php
- app/Application/Payment/Services/RecordCustomerRefundOperation.php
- app/Application/Payment/Services/AllocateRefundAcrossComponents.php
- app/Application/Inventory/Services/AutoReverseRefundedStoreStockInventory.php
- app/Application/Inventory/Services/RefundedStoreStockComponentTargets.php
- app/Application/Note/Services/RefundImpactPayloadBuilder.php

Projection/report files if reporting/projection is touched:

- database/migrations/2026_04_19_100100_create_note_history_projection_table.php
- app/Application/Note/Services/NoteHistoryProjectionService.php
- app/Adapters/Out/Note/DatabaseNoteHistoryProjectionSourceReaderAdapter.php
- app/Adapters/Out/Note/DatabaseNoteHistoryProjectionWriterAdapter.php
- app/Adapters/Out/Note/Queries/NoteHistoryAggregationSubqueries.php
- app/Adapters/Out/Note/Queries/NoteHistoryComponentLineSummarySubquery.php
- app/Adapters/Out/Note/Queries/NoteHistoryLegacyLineSummarySubquery.php
- app/Adapters/Out/Reporting/DatabaseTransactionReportingSourceReaderAdapter.php
- app/Adapters/Out/Reporting/DatabaseOperationalProfitReportingSourceReaderAdapter.php
- app/Adapters/Out/Reporting/DatabaseTransactionCashLedgerReportingSourceReaderAdapter.php
- app/Adapters/Out/Reporting/DatabaseInventoryMovementReportingSourceReaderAdapter.php

UI files only after backend plan is stable:

- resources/views/cashier/notes/workspace/create.blade.php
- resources/views/cashier/notes/workspace/partials/refund-modal.blade.php
- resources/views/cashier/notes/partials/refund-modal.blade.php
- resources/views/cashier/notes/partials/refund-form.blade.php
- public/assets/static/js/pages/cashier-note-refund.js
- public/assets/static/js/pages/cashier-note-workspace/rows.js
- public/assets/static/js/pages/cashier-note-workspace/search.js
- public/assets/static/js/pages/cashier-note-workspace/summary.js
- public/assets/static/js/pages/cashier-note-workspace/payment-flow.js
- public/assets/static/js/pages/cashier-note-workspace/boot.js

## Stop Conditions

Stop before implementation if:

- active slice cannot identify source of truth
- overpaid behavior is required but storage/workflow decision is unclear
- DB migration is needed but table contract is unclear
- report query would mix current and historical data
- UI change would hide backend defect
- delete path would remove unprotected financial evidence
- legacy record cannot be reconstructed and there is no uncertainty marker
- actor, reason, or audit source is missing for sensitive mutation
- file would exceed 100 app lines and the split plan is unclear
- test failure reason is not understood

## File Size Rule

make verify runs audit-line-count through audit-contract.

Files in app must stay within 100 lines unless they have a valid audit bypass token.

Default behavior:

    If a file wants to exceed 100 lines, split it cleanly.
    Do not compress dense logic to satisfy the number.
    Do not add bypass casually.

## Verification Rule

Before claiming final safe state:

    make verify

For sensitive finance slices, also run targeted and focused tests before make verify.

A slice is not fixed just because make verify passes.

A slice is fixed only when the active issue has:

- source map
- decision used
- RED proof or source-gap proof
- minimal production patch
- targeted GREEN proof
- focused blast-radius proof
- docs update
- residual gap list

## Next Session Opening Prompt

Use this prompt in the next AI session:

    Kita lanjut HyperPOS dari handoff edit/refund sniper.

    Baca dulu:
    docs/01_standards/0001_index.md
    docs/01_standards/0002_decision_policy.md
    docs/99_archive/handoff/v2/edit_refund_sniper/README.md
    docs/99_archive/handoff/v2/edit_refund_sniper/0001_verify_baseline_and_next_session_handoff.md
    docs/99_archive/handoff/v2/note_finance/0003_note_revision_refund_ledger_ai_reading_map.md
    docs/03_blueprints/finance/0006_note_revision_refund_ledger.md
    docs/03_blueprints/finance/0007_note_revision_refund_ledger_dod.md
    docs/03_blueprints/finance/0008_note_revision_refund_ledger_workflow.md

    Local baseline terakhir:
    branch main
    HEAD 6425a8b7
    make verify sebelumnya sempat gagal di ReportingReadModelContractFeatureTest karena compatibility docs path hilang.
    Patch compatibility docs dibuat:
    docs/02_architecture/adr/0009-reporting-as-read-model.md
    docs/03_blueprints/workflow_v1.md
    User melaporkan verification sudah passed, tapi exact final output belum dipaste di handoff.

    Tugas awal sesi ini:
    1. Ambil local baseline proof.
    2. Jangan patch dulu.
    3. Audit source spesifik untuk active slice.
    4. Rekomendasi first slice adalah Revision Settlement and Customer Balance Foundation, tapi validasi dulu dari source.
    5. Gunakan response shape:
       FACT, GAP, ASSUMPTION, DECISION, ACTIVE STEP, FILES TO TOUCH, FILES NOT TO TOUCH, COMMAND, EXPECTED PROOF, NEXT.

    Aturan:
    - local command output menang
    - no assumption
    - no code edit sebelum source audit dan decision lock
    - UI bukan financial truth
    - no ledger/history rewrite
    - file app >100 lines harus dipecah rapi
    - user handle commit/push manual

## Suggested Next Step After Creating This Folder

Run:

    git status --short --untracked-files=all
    git diff --stat
    php artisan test tests/Feature/Reporting/ReportingReadModelContractFeatureTest.php
    make verify

Then user can commit manually if clean.

## Progress Snapshot

Final Goal Progress:

    0% for full edit/refund ledger implementation.
    No domain implementation has started in this handoff.

Main Process Progress:

    25% for session-chain preparation.
    Docs entrypoint and sniper handoff folder are being created.

Sub-step Progress:

    100% for handoff folder bootstrap once README and this file exist.

Proof:

    User reported make verify passed after compatibility-doc fix.
    Exact final output not pasted.
    Next session should request or run local proof before relying on it.

## Session Context Health

    58% caution.
    Enough context for handoff, but next implementation should start from this folder and local proof.
