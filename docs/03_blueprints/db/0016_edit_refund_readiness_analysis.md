# Edit Refund Readiness Analysis

## Status

Analysis draft.

This document is not an implementation patch.

This document does not authorize production code changes, migration changes, UI changes, report changes, or refund logic changes.

## Purpose

Define the safe readiness boundary before changing edit, revision, refund, surplus, payment settlement, inventory, projection, or cashier UI behavior.

The goal is to prevent create-flow assumptions from being applied to edit and refund lifecycle paths.

## Source Priority Used

1. Current route/source proof.
2. Latest ADR or blueprint nearest to the domain.
3. Error log with proof.
4. Existing handoff with proof.
5. Older archived handoff.
6. Assumption, only when explicitly marked.

## FACT

### Active route map

| Surface | Active route | Controller | Request | Application entry | Finding |
| --- | --- | --- | --- | --- | --- |
| Create workspace | POST /notes/workspace/store | app/Adapters/In/Http/Controllers/Note/StoreTransactionWorkspaceController.php | app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspaceRequest.php | app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php | Active create route. This is initial transaction flow. |
| Admin edit/revision workspace submit | PATCH /admin/notes/{noteId}/workspace | app/Adapters/In/Http/Controllers/Note/StoreNoteRevisionController.php | app/Adapters/In/Http/Requests/Note/StoreNoteRevisionRequest.php | app/Application/Note/UseCases/CreateNoteRevisionHandler.php | Active admin edit submit is revision-based. |
| Cashier edit/revision workspace submit | PATCH /cashier/notes/{noteId}/workspace | app/Adapters/In/Http/Controllers/Note/StoreNoteRevisionController.php | app/Adapters/In/Http/Requests/Note/StoreNoteRevisionRequest.php | app/Application/Note/UseCases/CreateNoteRevisionHandler.php | Active cashier edit submit is revision-based and passes cashier note access middleware. |
| Admin refund submit | POST /admin/notes/{noteId}/refunds | app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php | app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php | app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php | Active admin refund flow builds backend plan then commits transaction. |
| Cashier refund submit | POST /cashier/notes/{noteId}/refunds | app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php | app/Adapters/In/Http/Requests/Note/RecordClosedNoteRefundRequest.php | app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php | Active cashier refund flow builds backend plan then commits transaction. |
| Candidate or legacy update workspace | No active route proven from routes/web/note.php | app/Adapters/In/Http/Controllers/Note/UpdateTransactionWorkspaceController.php | app/Adapters/In/Http/Requests/Note/UpdateTransactionWorkspaceRequest.php | app/Application/Note/UseCases/UpdateTransactionWorkspaceHandler.php | Source exists but route binding is not proven. Do not patch this path until route proof or dead-path decision exists. |

### Active create behavior

Create workspace may use grand total as initial payable because no old note money exists.

Create workspace can safely remain the initial transaction flow while edit and refund readiness is analyzed separately.

Create workspace is not automatically a valid model for edit/revision/refund.

### Active edit behavior

Active edit submit is revision-based.

StoreNoteRevisionRequest normalizes update-shaped workspace payload but forces inline_payment decision to skip.

Therefore current active revision submit does not combine revision and inline payment.

Any future combined revision-plus-payment behavior requires explicit ADR or decision lock first.

### Active refund behavior

RecordClosedNoteRefundController accepts selected row ids, refunded date, and reason.

It rejects non-close note status before resolving the refund plan.

SelectedNoteRowsRefundPlanResolver builds backend refund plan from active note rows, settlement projection, payment component allocations, and refund component allocations.

RecordSelectedRowsRefundPlanTransaction commits refund buckets, cancels selected active rows, optionally finalizes refunded note, syncs note history projection, writes audit, and commits or rolls back through TransactionManagerPort.

### Locked architecture direction

The locked direction for serious edit/refund lifecycle is Ledger plus Revision Snapshot plus Current Projection.

Note revisions are immutable business snapshots.

Work items are current operational rows or active projection, not final historical truth.

Payment and refund records are financial ledger events.

Inventory movements are stock ledger events.

Current note history projection is fast read model, not historical truth.

UI and API are transport adapters only.

### Known high-risk paths

The following paths must not be changed casually:

| Path | Risk |
| --- | --- |
| app/Adapters/Out/Note/WorkItemDeletesTrait.php | Can physically delete current operational work item rows while preserving refund-referenced historical anchors. |
| app/Adapters/Out/Payment/DatabasePaymentComponentAllocationWriterAdapter.php | Can delete or rebuild note payment allocations. |
| app/Application/Note/Services/ApplyNoteRevisionAsActiveReplacement.php | Applies revision replacement into active root note and coordinates payment allocation replay and projection sync. |
| app/Application/Note/Services/NoteReplacementPaymentAllocationReconciler.php | Captures, deletes, and rebuilds payment allocations during revision. |
| app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php | Reverses inventory, deletes old rows, recreates replacement rows. |
| app/Application/Note/Services/CancelSelectedRowsAndSyncActiveNoteTotal.php | Cancels selected active rows and updates active note total during refund. |
| app/Application/Payment/Services/RecordSelectedRowsRefundPlanTransaction.php | Commits refund effects, row cancellation, finalization, projection, and audit. |
| app/Application/Note/Services/NoteHistoryProjectionService.php | Recomputes current read projection after mutation. |

### Known proven risk history

| Error log | Proven lesson |
| --- | --- |
| 0004 refunded work items survive revisions and inflate stock | Refund-referenced historical work item anchors can leak into current operational rows and duplicate inventory reversal if current/historical boundary is wrong. |
| 0005 note revision silently drops overpaid allocations | Downward revision must not silently drop overpaid excess. Current safe behavior is reject and rollback until explicit surplus model handles it. |
| 0010 revision reallocation can lose concurrent payments | Revision allocation capture, delete, and rebuild must serialize with same-note payment mutations through note-level lock protocol. |
| 0017 workspace edit payments ignore existing note payments | Payment after existing allocation must use outstanding settlement state, not raw note total. Invalid route characterization was discarded because active revision request forces inline payment skip. |
| 0018 refunded notes bypass cashier closed-note guards | Refunded note must be terminal for cashier mutation routes unless explicit admin correction or reopen flow allows otherwise. |

## GAP

### Source proof gaps

1. Full implementation status of every surplus table, reader, writer, and report reader was not exhaustively mapped in this document.
2. Full implementation status of canonical audit_events / audit_event_snapshots writers was not exhaustively mapped here.
3. Full browser-executed UI behavior was not verified.
4. Full make verify was not run in this analysis step.
5. True two-connection concurrency stress proof was not performed in this analysis step.
6. Full report/export behavior after edit/refund/surplus/refund_paid was not verified in this analysis step.
7. Full customer identity readiness is not proven here.
8. Full route binding status of UpdateTransactionWorkspaceController remains unproven from active route map.

### Domain gaps

1. Combined revision submit plus payment is not authorized.
2. Customer credit is blocked until customer identity is stable.
3. Customer balance entries must not be introduced blindly while stable customer identity remains unresolved.
4. Downward revision with surplus must be explicit: overpaid_pending, refund_due, refund_paid, or future customer credit. It must never disappear.
5. refund_due must not be silently consumed by later revisions.
6. refund_paid must remain actual cash-out and must not be silently reclaimed by later revisions.
7. Refund money effect and stock return effect must remain separate backend-computed effects.
8. UI must not decide refund amount, payable amount, surplus amount, credit amount, or inventory effect.

### Test gaps

Minimum missing or not-yet-reconfirmed characterization before production changes:

1. Active revision after partial payment.
2. Active revision after ordinary refund.
3. Active revision after refund_due exists.
4. Active revision after surplus refund_paid exists.
5. Active refund after revision replacement.
6. Active refund rejects stale historical row id and accepts current replacement row id.
7. Active refund money effect without stock return if business rule requires it.
8. Active refund stock return without money effect if business rule requires it.
9. Edit payment calculator fallback without JavaScript.
10. Report/cash ledger distinction between customer_refunds and note_revision_surplus_refund_payments.

## ASSUMPTION

### A1 - Document location

Assumption:

This analysis is stored as docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md.

Reason:

The analysis is DB and migration-readiness heavy, and the previous active matrix is docs/03_blueprints/db/0015_create_edit_transaction_contract_matrix.md.

Risk if wrong:

The document may belong under finance rather than db.

Containment:

If owner prefers finance location, move this document before any implementation slice starts.

### A2 - Phase naming

Assumption:

This step is named Phase 1C.

Reason:

Phase 1B was create/edit source inspection addendum.

Risk if wrong:

Naming mismatch only affects lifecycle tracking, not runtime behavior.

Containment:

Rename before implementation.

### A3 - No runtime patch in this step

Assumption:

Owner wants analysis first and no production runtime patch in this response.

Reason:

Owner explicitly warned that create is not ready as structure for edit/refund and asked for analysis first.

Risk if wrong:

If owner intended code patch now, this response is intentionally conservative.

Containment:

After this analysis is accepted, choose one active implementation or characterization-test slice.

## DECISION

### D1 - Do not start from UI

Do not start edit/refund readiness from Blade or JavaScript.

Reason:

Blueprint rejects UI-only financial truth.

Backend must compute settlement, refund, surplus, and inventory effects.

UI may display and assist only.

### D2 - Do not generalize create flow into edit/refund

Create workspace is initial transaction flow.

Edit/revision/refund must account for carried payments, carried refunds, surplus, refund_due, refund_paid, inventory reversal/reissue, current projection, and immutable snapshots.

Therefore create flow can be tested, but it must not become the template for final edit/refund mutation.

### D3 - Keep active revision submit payment-skip until explicit decision changes it

StoreNoteRevisionRequest forcing inline_payment skip is a safety boundary.

Do not merge revision submit and payment submit unless a later accepted ADR explicitly authorizes it and backend settlement preview owns payable amount.

### D4 - Treat UpdateTransactionWorkspaceHandler as unproven route path

Do not patch UpdateTransactionWorkspaceHandler as active production edit behavior until route binding proof or dead-path decision exists.

### D5 - First safe direction is backend settlement/readiness characterization

Before production mutation, characterize backend settlement and refund-readiness behavior around active routes and existing ADRs.

### D6 - Surplus/refund_due/refund_paid must remain explicit records

Do not encode surplus, refund_due, or refund_paid only as UI text, note status text, or loose audit JSON.

Use explicit settlement/disposition/refund-paid records according to accepted ADRs and current source status.

### D7 - Customer credit remains blocked without customer identity contract

Do not introduce customer_credit or customer_balance_entries as implementation before stable customer identity is decided.

## Readiness Matrix

| Area | Current readiness | Blocker before broad edit/refund implementation | Next proof needed |
| --- | --- | --- | --- |
| Create workspace | Safe as initial create flow | Not sufficient for edit/refund lifecycle | Keep create tests separate from edit/refund tests. |
| Active revision submit | Revision-based, payment skipped | Combined revision payment not authorized | Characterize settlement after revision states. |
| Payment after edit/revision | Must be backend settlement-preview driven | Raw grand total and JS-only math invalid | HTTP or application proof for payable from backend context. |
| Ordinary selected-row refund | Backend plan and transaction exist | Need current/historical row boundary and effect separation proof | Characterize refund after revision. |
| Surplus overpaid_pending | Explicit settlement direction exists | Need current implementation status and later-revision interaction proof | Characterize downward paid revision and settlement persistence. |
| refund_due | Explicit disposition direction exists | Must not be silently consumed by later revision | Characterize later revision with existing refund_due. |
| surplus refund_paid | Separate execution table direction exists | Must not reuse customer_refunds or trigger component refund/inventory side effects | Characterize cash ledger/report reader behavior later. |
| Inventory revision/refund | Movement ledger exists | Must avoid duplicate reversal and stale historical anchors | Characterize revision/refund store-stock cases. |
| Projection | syncNote exists and is called in key flows | Projection is derived, not truth | Assert projection output after each tested mutation. |
| Reporting | Version-mode direction exists | Current reports may mix current/historical if not explicit | Do not patch reports until version mode/read source is locked. |
| UI | Existing create/edit workspace exists | UI must not compute final financial truth | Render backend explanation only after backend context exists. |

## Stop Conditions

Stop before implementation if any of these happen:

1. The active route cannot be proven.
2. A patch touches high-risk delete/rebuild path without current/historical proof.
3. A patch changes payment allocation replay without settlement tests.
4. A patch changes refund behavior without component allocation and double-refund proof.
5. A patch changes inventory behavior without movement ledger proof.
6. A patch relies on JavaScript for payable/refund/surplus truth.
7. A patch stores sensitive finance state only in loose JSON audit.
8. A patch introduces customer credit without customer identity contract.
9. A patch changes reports without explicit report version mode.
10. A patch claims fixed without RED or source gap, targeted GREEN, focused blast-radius proof, and residual gap list.

## Recommended Next Slice

### Slice name

Phase 1D - Active edit/refund settlement characterization.

### Slice type

Tests and source-map only unless RED result proves a narrow backend defect.

### Primary goal

Prove how active revision and refund behave with existing settlement, refund_due, and refund_paid records before any implementation patch.

### First target scenarios

1. Paid note revised downward creates or preserves explicit surplus state.
2. refund_due exists, later revision must not silently consume it.
3. surplus refund_paid exists, later revision must not treat it as reusable money.
4. Active refund after revision uses current row ids, not stale historical row ids.
5. Projection after revision/refund matches explicit financial state.

### Files likely touched first

Tests only:

- tests/Feature/Note/...
- tests/Feature/Payment/...

No production file should be touched in first pass unless RED proof is produced and a narrow patch is explicitly authorized.

## Next Commands

Suggested verification after creating this document:

    grep -n \
      -e "## FACT" \
      -e "## GAP" \
      -e "## ASSUMPTION" \
      -e "## DECISION" \
      -e "## Readiness Matrix" \
      -e "## Stop Conditions" \
      -e "## Recommended Next Slice" \
      docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md

Suggested source guardrail verification:

    grep -n \
      -e "Do not generalize create flow into edit/refund" \
      -e "Do not patch UpdateTransactionWorkspaceHandler" \
      -e "Customer credit remains blocked" \
      -e "StoreNoteRevisionRequest forcing inline_payment skip is a safety boundary" \
      docs/03_blueprints/db/0016_edit_refund_readiness_analysis.md
