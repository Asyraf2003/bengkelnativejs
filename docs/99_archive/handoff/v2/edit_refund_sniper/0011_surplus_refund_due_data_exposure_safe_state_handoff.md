# Handoff 0011 - Surplus Refund Due Data Exposure Safe State

## Metadata

- Date: 2026-05-13
- Sequence: 0011
- Scope: refund_due admin transport plus note detail data exposure safe state
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0010_surplus_refund_due_admin_transport_handoff.md
- Status: pushed latest and safe after make verify by owner statement
- Owner workflow: owner handles commit and push manually

## Purpose

This handoff prevents the next session from restarting analysis from admin transport.

The admin HTTP transport from handoff 0010 is already implemented and verified.

After handoff 0010, the data exposure slice for note detail surplus disposition payload was implemented and verified enough for the current safe state.

Owner then reported:

- make verify aman
- git push terbaru
- test aman

Per session contract, owner statement clean, pushed, latest, or make verify pass is FACT.

Therefore the next session must treat this as the latest pushed safe state unless new local command output contradicts it.

## Baseline Facts

Owner baseline facts still active:

- Owner always commits and pushes manually.
- Local and repo are identical after push except ignored files.
- Owner statement clean, pushed, latest, or make verify pass is FACT.
- Local command output and owner statement win over GitHub or docs when there is conflict.
- Do not ask for git status, git log, git diff, git diff --check, or make verify as ritual.
- Git and make verify are used only when there is a real trigger.

Latest owner proof statement for this handoff:

- make verify aman
- git push terbaru
- test aman

Exact make verify output count was not pasted in chat.

Exact final post-push test command and count were not pasted in chat.

This is not a blocker because owner statement is accepted baseline proof.

## Required Source Context Already Resolved

The next session does not need to rediscover these decisions.

Admin transport source analysis was completed in handoff 0010.

Data exposure source analysis found:

- NoteDetailPageDataBuilder built the detail page payload from note, workspace panel, operational totals, billing rows, history, refund options, and revision view.
- NoteDetailNotePayloadBuilder did not expose surplus_disposition before the data exposure patch.
- NoteRevisionSurplusDispositionReaderPort only exposed findPendingBySettlementId before the data exposure patch.
- DatabaseNoteRevisionSurplusDispositionAdapter only supported lookup by settlement id before the data exposure patch.
- Admin detail UI needs pending surplus by note root id, not a manual settlement id.
- Therefore a by-note-root read path and a small view data builder were needed before Blade work.

## Locked Decisions

Domain and transport decisions:

- refund_due-only remains active.
- refund_due is a surplus disposition decision.
- refund_due is not refund_paid.
- refund_due does not mean money already left the business.
- overpaid_pending is not revenue.
- overpaid_pending is not automatic refund paid.
- overpaid_pending is not automatic customer credit.
- customer_credit remains blocked until customer identity is locked.
- customer_balance_entries remains out of scope.
- refund_paid execution remains out of scope.
- PostgreSQL implementation is out of scope.
- Go API implementation is out of scope.
- audit_events and audit_event_snapshots are canonical for new finance-sensitive audit.
- audit_logs remains legacy or compatibility storage.
- UI is not financial truth.
- Controller and route are transport adapters.
- Report query is out of scope.

Permission decision:

- First refund_due surplus disposition transport is admin-only.
- Admin transport uses the existing admin page boundary.
- Admin transport intentionally does not reuse EnsureTransactionEntryAllowed.
- Reason: ADR 0027 says not to reuse admin_transaction_entry capability for surplus disposition.

UI/data decision:

- UI must receive backend-generated pending surplus state.
- UI must not compute after_pending_rupiah.
- UI must not compute final finance truth.
- UI must not treat refund_due as refund_paid.
- UI must not introduce customer_credit.
- UI must not create customer_balance_entries.
- Application data payload should not call route helpers.
- action_url remains transport/view concern.

## Completed Before This Handoff

### 1. Backend foundation

Already completed in earlier handoffs:

- canonical audit writer foundation
- note_revision_surplus_dispositions migration
- surplus disposition DTOs
- surplus disposition reader/writer ports
- surplus disposition DB adapter
- CreateNoteRevisionSurplusRefundDue backend use case

Proof from handoff 0009:

- targeted use case proof passed 6 tests / 26 assertions
- focused backend contract proof passed 14 tests / 77 assertions

### 2. Minimum admin transport

Completed in handoff 0010.

Files created:

- app/Adapters/In/Http/Controllers/Admin/Note/CreateNoteRevisionSurplusRefundDueController.php
- app/Adapters/In/Http/Requests/Note/CreateNoteRevisionSurplusRefundDueRequest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php

File modified:

- routes/web/note.php

Route implemented:

    POST /admin/notes/revision-settlements/{settlementId}/refund-due

Route name:

    admin.notes.revision-settlements.refund-due.store

Route boundary:

- inside admin notes route group
- auth
- EnsureAdminPageAccess
- app.shell
- intentionally outside EnsureTransactionEntryAllowed

Targeted proof from handoff 0010:

    Tests: 13 passed (61 assertions)

Focused blast-radius proof from handoff 0010:

    Tests: 21 passed (122 assertions)

### 3. Detail data exposure for surplus disposition

Completed after handoff 0010.

Files created or changed:

- app/Application/Note/Services/NoteRevisionSurplusDispositionActionViewDataBuilder.php
- app/Ports/Out/Note/NoteRevisionSurplusDispositionReaderPort.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusDispositionAdapter.php
- app/Application/Note/Services/NoteDetailPageDataBuilder.php
- app/Application/Note/Services/NoteDetailNotePayloadBuilder.php

Behavior added:

- Reader port now supports pending lookup by note root id.
- Database adapter can return unresolved pending surplus items for a note root id.
- Data exposure builder creates a UI-safe surplus_disposition payload.
- Note detail page data now includes surplus_disposition inside note payload.
- Payload is backend-generated.
- Payload does not include action_url.
- Payload does not compute refund_paid.
- Payload does not create customer_credit.
- Payload does not depend on report query.

Expected payload shape:

    surplus_disposition
        has_pending_refund_due_action
        pending_items

Each pending item:

    note_revision_settlement_id
    note_revision_id
    note_root_id
    surplus_rupiah
    active_disposition_rupiah
    unresolved_pending_rupiah
    disposition_type
    amount_default_rupiah
    reason_required

Important:

- disposition_type is refund_due.
- amount_default_rupiah equals unresolved_pending_rupiah.
- reason_required is true.
- action_url is intentionally not included in application payload.

## Proof Recorded In Session

Syntax proof for data exposure files:

    No syntax errors detected in app/Application/Note/Services/NoteRevisionSurplusDispositionActionViewDataBuilder.php
    No syntax errors detected in app/Ports/Out/Note/NoteRevisionSurplusDispositionReaderPort.php
    No syntax errors detected in app/Adapters/Out/Note/DatabaseNoteRevisionSurplusDispositionAdapter.php
    No syntax errors detected in app/Application/Note/Services/NoteDetailPageDataBuilder.php
    No syntax errors detected in app/Application/Note/Services/NoteDetailNotePayloadBuilder.php

Targeted existing proof after data exposure patch:

    PASS  Tests\Feature\Note\DatabaseNoteRevisionSurplusDispositionAdapterTest
    ✓ writer persists refund due surplus disposition
    ✓ reader returns unresolved pending after active disposition
    ✓ reader ignores non overpaid pending settlement

    PASS  Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueControllerFeatureTest
    ✓ admin can create refund due from pending surplus settlement
    ✓ refund due request requires valid amount and reason
    ✓ use case failure redirects back with refund due error
    ✓ cashier cannot access admin refund due route
    ✓ admin without transaction capability can create refund due

    PASS  Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueHandlerTest
    ✓ rejects non admin actor
    ✓ rejects empty reason
    ✓ rejects missing or invalid pending settlement
    ✓ rejects amount greater than unresolved pending
    ✓ writes audit event snapshots disposition and updates pending
    ✓ rolls back audit event and disposition when second write fails

    Tests: 14 passed (61 assertions)
    Duration: 6.02s

Owner final safe-state statement after this:

- make verify aman
- git push terbaru
- test aman

Treat this as final safe-state proof for this slice unless contradicted by newer local command output.

## Current State

The refund_due chain now has:

- backend transaction/audit/disposition use case
- canonical audit writer usage
- note_revision_surplus_dispositions persistence
- admin HTTP route/controller/request
- admin route tests
- detail payload data exposure foundation
- pushed latest safe state by owner statement
- make verify safe by owner statement
- test safe by owner statement

Still not implemented:

- Blade card/form for refund_due
- JavaScript enhancement for refund_due form
- browser/manual QA
- refund_paid execution
- customer_credit
- customer_balance_entries
- report visibility for refund_due liability
- cancel or reverse refund_due use case
- idempotency token for repeated submit
- explicit row lock/concurrency hardening for high-contention multi-admin disposition
- audit_events timeline UI read model

## Files Not To Reopen Without Trigger

Do not reopen these unless new failing proof appears:

- CreateNoteRevisionSurplusRefundDue backend use case files
- canonical audit writer foundation files
- admin transport route/controller/request files
- note_revision_surplus_dispositions migration
- refund_due controller feature test
- data exposure builder and reader patch

Do not ask for git status/log/diff as ceremony.

Do not ask for make verify as ceremony.

Owner already reported latest pushed safe state.

## Next Active Step

Recommended next step:

Design minimal Blade rendering for admin refund_due action using existing payload.

Start from payload contract, not from route or use case.

Do not restart admin transport analysis.

Do not restart backend use case analysis.

Do not inspect report files.

Do not inspect refund_paid files.

Do not inspect customer_credit files.

Do not inspect customer_balance_entries files.

Do not broaden into PostgreSQL or Go API.

## Next Step Inspection Scope

Start with only:

- resources/views/shared/notes/partials/payment-summary-actions.blade.php
- resources/views/shared/notes/show.blade.php
- app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php
- app/Application/Note/Services/NoteDetailPageDataBuilder.php
- app/Application/Note/Services/NoteDetailNotePayloadBuilder.php

If test fixture is needed, use existing fixture patterns from:

- tests/Support/SeedsMinimalNotePaymentFixture.php
- tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php
- tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php

Do not perform broad repo archaeology.

## Suggested UI Rendering Contract

Recommended minimal UI shape:

- Render a separate small card or section inside note detail payment/status action area.
- Use note.surplus_disposition.has_pending_refund_due_action.
- Iterate note.surplus_disposition.pending_items.
- Show unresolved pending amount with Rupiah format.
- Show form action using:
  - route('admin.notes.revision-settlements.refund-due.store', ['settlementId' => item.note_revision_settlement_id])
- Form fields:
  - amount_rupiah default to amount_default_rupiah
  - reason text input or textarea
- Submit button label should say refund_due or “Tandai Refund Due”.
- UI must not say refund paid.
- UI must not imply money already left business.
- UI must not mention customer credit.
- UI must not compute after_pending_rupiah.
- UI must rely on backend validation and use case.

## Suggested UI Test Plan

Add a focused rendered detail test.

Possible file:

- tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php

Minimum tests:

1. Admin detail renders refund_due action when pending surplus exists.

Expected assertions:

- response OK
- sees pending amount formatted
- sees route admin.notes.revision-settlements.refund-due.store
- sees amount_rupiah input
- sees reason input
- sees wording refund_due or Refund Due
- does not see refund_paid wording
- does not see customer_credit wording

2. Admin detail does not render refund_due action when no pending surplus exists.

Expected assertions:

- response OK
- does not see refund_due route
- does not show misleading action

3. Existing admin workspace/detail adjacency remains green.

Expected command after patch:

    php artisan test \
      tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php \
      tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php \
      tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php

## Suggested Files To Touch Next

Likely:

- resources/views/shared/notes/partials/payment-summary-actions.blade.php
- tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php

Possibly:

- resources/views/shared/notes/partials/surplus-refund-due-actions.blade.php

Avoid touching:

- routes/web/note.php
- admin transport controller/request
- backend use case
- report query
- migration
- refund_paid
- customer_credit
- customer_balance_entries

## Progress Snapshot

Final Goal Progress:

- 62 percent for refund_due admin-operable chain.
- Reason: backend use case, admin transport, detail payload exposure, make verify safe state, and latest push are proven by owner statement and prior tests.
- Remaining: UI render/action form, report visibility, refund_paid execution, future hardening.

Main Process Progress:

- 86 percent for backend plus admin transport plus data exposure chain.
- Reason: current slice is pushed and safe; UI rendering remains.

Sub-step Progress:

- 100 percent for safe-state handoff preparation after transport and data exposure.
- Proof:
  - handoff 0010 transport proof
  - data exposure syntax proof
  - data exposure targeted proof 14 passed / 61 assertions
  - owner statement make verify aman
  - owner statement git push terbaru
  - owner statement test aman

## Session Context Health

84 percent.

Reason:

This chain now includes backend foundation, admin transport, route boundary, capability exclusion decision, data exposure reader/payload changes, make verify safe-state, and push status.

A new session should start from this handoff to avoid repeated analysis.

## Next Session Opening Prompt

    Kita lanjut HyperPOS dari edit/refund sniper handoff 0011.

    Baca berurutan:
    docs/01_standards/0001_index.md
    docs/01_standards/0002_decision_policy.md
    docs/99_archive/handoff/v2/edit_refund_sniper/README.md
    docs/99_archive/handoff/v2/edit_refund_sniper/SESSION_CONTRACT.md
    docs/99_archive/handoff/v2/edit_refund_sniper/0011_surplus_refund_due_data_exposure_safe_state_handoff.md
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
    - Admin transport refund_due from handoff 0010 is done.
    - Route exists: admin.notes.revision-settlements.refund-due.store.
    - Controller exists: CreateNoteRevisionSurplusRefundDueController.
    - Request exists: CreateNoteRevisionSurplusRefundDueRequest.
    - Data exposure for note detail surplus_disposition payload is done.
    - NoteRevisionSurplusDispositionReaderPort supports lookup by note root id.
    - DatabaseNoteRevisionSurplusDispositionAdapter supports pending lookup by note root id.
    - NoteRevisionSurplusDispositionActionViewDataBuilder exists.
    - NoteDetailPageDataBuilder includes surplus disposition payload.
    - NoteDetailNotePayloadBuilder exposes note.surplus_disposition.
    - make verify aman by owner statement.
    - git push terbaru by owner statement.
    - test aman by owner statement.

    Latest proof:
    - Admin transport targeted proof: 13 passed / 61 assertions.
    - Admin transport focused proof: 21 passed / 122 assertions.
    - Data exposure targeted proof: 14 passed / 61 assertions.
    - Owner reported make verify safe and latest pushed.

    Locked decisions:
    - refund_due-only remains active.
    - refund_due is a surplus disposition decision, not refund_paid.
    - refund_due does not mean money already left the business.
    - customer_credit remains blocked until customer identity is locked.
    - customer_balance_entries is out of scope.
    - refund_paid execution is out of scope.
    - PostgreSQL implementation is out of scope.
    - Go API implementation is out of scope.
    - audit_events and audit_event_snapshots are canonical for this new finance-sensitive audit.
    - audit_logs remains legacy/compatibility and must not become final finance audit truth.
    - Transport must call existing application use case.
    - Controller/UI/report must not compute final finance truth.
    - Admin transport intentionally does not reuse EnsureTransactionEntryAllowed because ADR 0027 says not to reuse admin_transaction_entry for surplus disposition.
    - UI payload must not include action_url from application layer.
    - UI must not compute after_pending_rupiah.
    - UI must not say refund_paid.

    Current next active target:
    Design minimal Blade rendering for admin refund_due action using existing note.surplus_disposition payload.

    Required scope:
    - Do not restart backend use case analysis.
    - Do not restart admin transport analysis.
    - Do not inspect report files.
    - Do not implement refund_paid.
    - Do not implement customer_credit.
    - Do not create customer_balance_entries.
    - Do not touch migrations.
    - First inspect only payment summary partial and relevant detail view.
    - Add focused rendered UI test before or with Blade patch.

    Suggested first inspection targets:
    resources/views/shared/notes/partials/payment-summary-actions.blade.php
    resources/views/shared/notes/show.blade.php
    app/Adapters/In/Http/Controllers/Admin/Note/NoteDetailPageController.php
    tests/Support/SeedsMinimalNotePaymentFixture.php
    tests/Feature/Note/AdminNoteWorkspaceReplacementFeatureTest.php
    tests/Feature/Note/CreateNoteRevisionSurplusRefundDueControllerFeatureTest.php

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

    Hard rule:
    One active step per response.
    No progress claim without proof.
    No broad repo archaeology.
    No UI implementation before rendered UI test plan.
