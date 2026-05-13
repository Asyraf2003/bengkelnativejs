# Handoff 0012 - Surplus Refund Due Blade UI

## Metadata

- Date: 2026-05-13
- Sequence: 0012
- Scope: admin note detail refund_due Blade rendering
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0011_surplus_refund_due_data_exposure_safe_state_handoff.md
- Status: UI targeted and focused proof passed locally
- Owner workflow: owner handles commit and push manually

## Purpose

This handoff records the admin Blade UI rendering slice for refund_due surplus disposition.

The previous handoff already completed:

- backend refund_due use case
- canonical audit write path
- note_revision_surplus_dispositions persistence
- admin transport route/controller/request
- note detail data exposure through note.surplus_disposition payload

This slice only renders the existing backend-generated payload into the admin note detail UI.

It does not restart backend analysis.

It does not change route, controller, request, application use case, migration, report query, refund_paid, customer_credit, or customer_balance_entries.

## Baseline Facts

Owner baseline facts remain active:

- Owner always commits and pushes manually.
- Local and repo are identical after push except ignored files.
- Owner statement clean, pushed, latest, or make verify pass is FACT.
- Local command output and owner statement win over GitHub or docs when there is conflict.
- Do not ask for git status, git log, git diff, git diff --check, or make verify as ritual.
- Git and make verify are used only when there is a real trigger.

## Locked Decisions

- refund_due-only remains active.
- refund_due is a surplus disposition decision.
- refund_due is not refund_paid.
- refund_due does not mean money already left the business.
- customer_credit remains blocked until customer identity is locked.
- customer_balance_entries remains out of scope.
- refund_paid execution remains out of scope.
- PostgreSQL implementation is out of scope.
- Go API implementation is out of scope.
- audit_events and audit_event_snapshots are canonical for this new finance-sensitive audit.
- audit_logs remains legacy or compatibility storage.
- Controller/UI/report must not compute final finance truth.
- UI payload must not include action_url from application layer.
- View layer may build transport route action.
- UI must not compute after_pending_rupiah.
- UI must not say refund_paid.
- UI must not mention customer_credit as an active action.

## Files Changed In This Slice

Created:

- tests/Feature/Note/AdminNoteSurplusRefundDueUiFeatureTest.php

Modified:

- resources/views/shared/notes/partials/payment-summary-actions.blade.php

## UI Behavior Added

Admin note detail now renders a refund_due action block inside the existing Status & Aksi Nota card when:

- note.surplus_disposition.has_pending_refund_due_action is true
- note.surplus_disposition.pending_items is not empty

For each pending item, the UI renders:

- pending refund due amount with Rupiah thousands separator
- POST form action using route admin.notes.revision-settlements.refund-due.store
- amount_rupiah input
- reason textarea
- submit button labeled Tandai Refund Due

The UI explicitly describes that Refund Due does not mean money already left the business.

The UI does not compute after_pending_rupiah.

The UI does not say refund_paid.

The UI does not expose customer_credit as an action.

## RED Proof

Before the Blade patch, the focused rendered UI test failed as expected:

    FAIL  Tests\Feature\Note\AdminNoteSurplusRefundDueUiFeatureTest
    ⨯ admin detail renders refund due action when pending surplus exists
    ✓ admin detail does not render refund due action without pending surplus

    Expected response HTML to contain:
    Tandai Refund Due

    Tests: 1 failed, 1 passed (7 assertions)

Meaning:

- admin detail route rendered successfully
- negative case already passed
- positive case failed only because refund_due UI was not rendered yet

## GREEN Proof

After the Blade patch, targeted UI proof passed:

    PASS  Tests\Feature\Note\AdminNoteSurplusRefundDueUiFeatureTest
    ✓ admin detail renders refund due action when pending surplus exists
    ✓ admin detail does not render refund due action without pending surplus

    Tests: 2 passed (14 assertions)

Focused adjacency proof passed:

    PASS  Tests\Feature\Note\AdminNoteSurplusRefundDueUiFeatureTest
    ✓ admin detail renders refund due action when pending surplus exists
    ✓ admin detail does not render refund due action without pending surplus

    PASS  Tests\Feature\Note\CreateNoteRevisionSurplusRefundDueControllerFeatureTest
    ✓ admin can create refund due from pending surplus settlement
    ✓ refund due request requires valid amount and reason
    ✓ use case failure redirects back with refund due error
    ✓ cashier cannot access admin refund due route
    ✓ admin without transaction capability can create refund due

    PASS  Tests\Feature\Note\AdminNoteWorkspaceReplacementFeatureTest
    ✓ admin can open and submit closed note workspace replacement as revision
    ✓ admin workspace config json escapes script breaking sequences from stored fields
    ✓ admin workspace config json escapes script breaking sequences from product label

    Tests: 10 passed (66 assertions)

## Verification Gaps

The following are still gaps unless owner provides proof or statement:

- full make verify after this UI slice
- latest push after this UI slice
- browser/manual QA
- JavaScript enhancement for refund_due form
- refund_paid execution
- customer_credit
- customer_balance_entries
- report visibility for refund_due liability
- cancel or reverse refund_due use case
- idempotency token for repeated submit
- explicit row lock or concurrency hardening for high-contention multi-admin disposition
- audit_events timeline UI read model

## Files Not To Reopen Without Trigger

Do not reopen these without new failing proof:

- backend refund_due use case files
- canonical audit writer foundation files
- admin route/controller/request files
- note_revision_surplus_dispositions migration
- data exposure builder and reader files
- report query files
- refund_paid files
- customer_credit files
- customer_balance_entries files

## Next Active Step

Recommended next step depends on owner safe-state statement.

If owner reports latest pushed and make verify safe after this UI patch:

- update this handoff status to pushed latest and safe after make verify by owner statement
- continue to the next refund_due chain target

If owner does not run final make verify yet:

- treat current state as targeted and focused UI proof only
- do not claim final safe state

Potential next technical target after safe-state closure:

- JavaScript progressive enhancement for refund_due submit UX, if needed
- or report visibility planning for refund_due liability
- or audit_events timeline read model planning

Do not implement refund_paid.

Do not implement customer_credit.

Do not create customer_balance_entries.

Do not touch migrations.

## Progress Snapshot

Final Goal Progress:

- 66 percent for refund_due admin-operable chain.
- Reason: backend use case, admin transport, detail payload exposure, and admin Blade rendering are now proven by targeted and focused tests.
- Remaining: final safe-state proof statement, report visibility, refund_paid execution, future hardening, optional JS/browser QA.

Main Process Progress:

- 92 percent for backend plus admin transport plus data exposure plus admin Blade action.
- Reason: current UI action is rendered and controller adjacency remains green.

Sub-step Progress:

- 100 percent for minimal admin Blade refund_due rendering.
- Proof:
  - RED UI proof: 1 failed / 1 passed / 7 assertions
  - GREEN targeted UI proof: 2 passed / 14 assertions
  - GREEN focused adjacency proof: 10 passed / 66 assertions

## Session Context Health

74 percent.

Mini-summary:

- Locked facts: refund_due-only, not refund_paid, not customer_credit, UI not finance truth.
- Current active result: minimal admin Blade refund_due render is proven targeted and focused.
- Latest proof: targeted 2 passed / 14 assertions, focused 10 passed / 66 assertions.
- Next safest step: owner safe-state statement or continue with next explicitly selected target without reopening backend.
