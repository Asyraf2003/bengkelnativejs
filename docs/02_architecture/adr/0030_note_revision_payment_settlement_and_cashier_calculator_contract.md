# ADR 0030 - Note Revision Payment Settlement And Cashier Calculator Contract

## Status

Draft for owner review.

## Context

HyperPOS supports transaction create, edit, revision, partial payment, full payment, refund, refund_due, and refund_paid.

The cashier-facing workflow must stay simple.

The system must automatically carry old paid money, old refunds, and explicit surplus/refund states into payment math.

Cashier must not manually calculate or re-enter old money.

Current source and test inventory show:

- create workspace inline payment has direct service coverage for existing legacy allocation
- revision settlement builder already carries paid and refunded totals into net settlement
- workspace update tests currently submit inline_payment skip
- StoreNoteRevisionRequest currently forces inline_payment skip for revision submit
- request payment validator still has grand-total-oriented validation wording and target amount behavior
- cashier payment JavaScript assists modal calculation from visible rows
- refund_due and refund_paid are explicit domain records and must not be silently consumed by later revision

## Problem

Edited or revised transaction payment math can become unsafe if the payment calculator or validator uses only the current visible row total.

Old money can be missed or double-counted.

Refunds can be subtracted twice.

refund_due can be silently consumed.

refund_paid can be treated as still available money even though it already left the business.

The result is cashier confusion and unexpected outstanding after the user believes payment is correct.

## Decision

Payment after edit or revision must be settlement-preview-driven.

Backend application services must provide the payable amount and explanation.

Blade and JavaScript may display and assist, but must not decide final payable amount.

Request validators may validate input shape and obvious invalid forms, but final amount acceptance must be owned by application/domain settlement logic.

## Source Of Truth

customer_payments plus payment_allocations or payment_component_allocations represent actual money received and allocated.

customer_refunds plus refund_component_allocations represent ordinary payment/component refund.

note_revision_settlements represents revision settlement snapshot:

- gross total
- carry-forward paid
- carry-forward refunded
- net paid
- outstanding
- surplus
- settlement status

note_revision_surplus_dispositions represents explicit surplus decisions such as refund_due.

note_revision_surplus_refund_payments represents actual surplus refund_paid cash-out.

Current work items and current revision projection represent current operational rows, not full financial history truth.

Reporting reads official records and must not invent settlement math.

## Route And Use Case Boundary

Create transaction workspace may use grand total as initial payable because no old note money exists.

Update transaction workspace may keep inline payment support only if it uses backend settlement payable.

Note revision submit currently forces inline_payment to skip.

That behavior remains the safe default until a later ADR explicitly allows combined revision-plus-payment submit.

Payment after revision must use latest settlement and carry-forward state.

## Cashier UX Contract

Cashier edits normally.

Cashier sees clear backend-derived information:

- current total
- already paid
- already refunded
- carried forward to revised note
- payable now
- surplus if any
- refund_due if any
- refund_paid if any

Cashier does not manually calculate old money.

Full payment pays backend payable.

Partial payment is validated against backend payable.

Cash received must be greater than or equal to backend payable or backend accepted partial amount.

Transfer payment must use backend payable or backend accepted partial amount.

Fallback form submit must remain valid without JavaScript.

JavaScript may format, focus, open modal, and show convenience calculations only.

## Math Contract

New transaction payable equals current total.

Existing note payable equals backend settlement outstanding, not raw grand total.

For revision:

- revised total greater than carried net money creates outstanding
- revised total equal to carried net money creates settled state
- revised total lower than carried net money creates surplus

Refunded money reduces carry-forward net exactly once.

refund_due remains liability and must not be silently consumed by a later revision.

refund_paid is actual cash-out and must not be silently reclaimed by a later revision.

Downward revision must not appear unpaid only because surplus exists.

External purchase lines affect note total and case cost or profit, but do not mutate inventory.

## Backend Calculator Contract

Introduce or identify a backend application service that can build cashier payment context.

Working name:

PreviewNoteWorkspacePaymentSettlement

Required output:

- note_id
- workspace_mode
- current_total_rupiah
- old_paid_rupiah
- old_refunded_rupiah
- carry_forward_net_rupiah
- refund_due_rupiah
- surplus_refund_paid_rupiah
- remaining_refund_due_rupiah
- settlement_status
- payable_rupiah
- surplus_rupiah
- payment_allowed
- explanation_lines

Controllers may pass this data to Blade.

Future API must call the same application service.

## Rejected Behaviors

Reject raw grand-total validation as final finance guard for edited or revised notes.

Reject JavaScript-only payable calculation.

Reject cashier manual old-money entry.

Reject silent consumption of refund_due in later revision.

Reject silent reclaim of refund_paid in later revision.

Reject report query patches that hide settlement mismatch.

Reject customer_refunds for surplus refund_paid.

Reject customer_credit or customer_balance_entries in this ADR.

Reject PostgreSQL, Go API, and dashboard work in this ADR.

## Scenario Matrix

| ID | Scenario | Expected Result |
| --- | --- | --- |
| S01 | New note full payment | payable equals grand total |
| S02 | New note partial payment | outstanding equals total minus paid |
| S03 | Existing note paid 40k total 100k pay full | payable equals 60k |
| S04 | Partially paid note revised upward | payable equals revised total minus carried net |
| S05 | Partially paid note revised equal to carried net | payable zero and settled |
| S06 | Partially paid note revised downward | surplus explicit, no hidden outstanding |
| S07 | Paid note revised downward | overpaid_pending settlement |
| S08 | Paid note revised upward | outstanding delta |
| S09 | Paid then refunded note revised equal to net paid | payable zero, refund not double-subtracted |
| S10 | Paid then refunded note revised upward | payable equals revised total minus net carried |
| S11 | refund_due exists then later revision | refund_due remains liability |
| S12 | refund_paid exists then later revision | refund_paid remains cash-out |
| S13 | Cash after edit | received must be at least backend payable |
| S14 | Transfer after edit | paid amount follows backend payable or accepted partial |
| S15 | External purchase create | note total includes external purchase contract |
| S16 | External purchase edit or revision | cost/profit traceable, inventory untouched |
| S17 | JavaScript disabled fallback | backend accepts or rejects using same payable |
| S18 | Stale draft payment amount | backend rejects stale amount exceeding current payable |

## Test Plan

Minimum first tests after this ADR is accepted:

- HTTP or validator test proving edit payment validates against backend payable, not raw grand total.
- Application test for partial payment plus refund plus revision equal to net paid.
- Application test for partial payment plus refund plus upward revision.
- Application test proving refund_due is not silently consumed by later revision.
- Application test proving refund_paid is not silently reclaimed by later revision.
- Render test showing backend-derived calculator context on cashier edit page.
- Fallback submit test proving JavaScript is not financial truth.
- External purchase edit or revision test proving cost/profit traceability and no inventory mutation.

## Reporting Contract

Reporting must read official records.

Reporting must distinguish:

- customer payments
- customer_refunds
- refund_due
- surplus_refund_paid
- remaining_refund_due
- settlement carry-forward where explicitly stored
- external purchase cost where relevant

Reporting must not invent carried-forward payment math.

## Out Of Scope

Production code patch.

Migration.

customer_credit.

customer_balance_entries.

PostgreSQL.

Go API.

Dashboard.

Blind UI polish.

refund_paid mutation foundation rewrite.

## Next Safe Step

After owner accepts this ADR, add RED tests for the route/use-case boundary before production patch.

Start with validator or HTTP proof for edit payment payable.

## Route Boundary Anchor

StoreNoteRevisionRequest currently forces inline_payment to skip for revision submit.

## Backend Payment Settlement Preview Boundary

Backend application/service must provide payable amount and explanation.

Blade/JS may display and assist only.

Request validator may validate payload shape only. Final accepted payment amount must come from backend payable/settlement logic, not UI calculator state and not raw grand total assumptions.

This preserves the route/application boundary between note revision submit and payment settlement until a later ADR explicitly decides to merge those flows.

Payment after edit or revision must be settlement-preview-driven by backend payable/settlement logic. Blade and JavaScript may display or assist only, and request validators may validate payload shape only. Final accepted payment amount must not be derived from UI calculator state or raw grand total assumptions.

## Implementation Verification

### Update 1 - Request validator pay_full cash boundary

Status: Fixed and locally verified for the request-validator boundary.

Scope:
- `app/Adapters/In/Http/Requests/Note/StoreTransactionWorkspacePaymentValidator.php`
- `tests/Unit/Adapters/In/Http/Requests/Note/StoreTransactionWorkspacePaymentValidatorTest.php`

Problem proven:
- `pay_full` cash validation used payload grand total as the cash target.
- Existing paid allocations / backend settlement payable were not considered at request-validator level.
- A payment after edit could be rejected before backend settlement/payable logic had authority to determine the accepted amount.

RED proof:
- Scenario: payload grand total `100000`, backend payable intent represented by cash received `60000`.
- Test: `StoreTransactionWorkspacePaymentValidatorTest::test_pay_full_cash_received_is_not_validated_against_payload_grand_total`
- Result: failed with `inline_payment.amount_received_rupiah`.
- Error: `Uang masuk cash tidak boleh kurang dari total yang dibayar.`
- Proof: `1 failed / 1 assertions`.

Patch:
- `pay_full` cash no longer compares `amount_received_rupiah` against payload grand total.
- `pay_partial` cash still compares `amount_received_rupiah` against explicit `amount_paid_rupiah`.
- Request validator remains shape/boundary validation only.
- Backend settlement/payable logic remains responsible for the accepted payable amount.

GREEN proof:
- Targeted validator proof: `1 passed / 1 assertions`.
- Validator regression proof: `2 passed / 2 assertions`.
- Focused validator + workspace update adjacency: `4 passed / 18 assertions`.

Regression locked:
- `pay_partial` cash with `amount_paid_rupiah = 60000` and `amount_received_rupiah = 50000` still fails on `inline_payment.amount_received_rupiah`.

Out of scope:
- Revision submit + payment merge.
- Customer credit.
- Customer balance entries.
- `customer_refunds` for surplus `refund_paid`.
- `refund_component_allocations` for surplus `refund_paid`.
- Refunded lifecycle trigger for surplus `refund_paid`.
- Inventory reversal for surplus `refund_paid`.
- JS/Blade cashier calculator changes.
- Report/export changes.
- PostgreSQL.
- Go API.
- Dashboard.

Verification gaps:
- Full `make verify` after this ADR 0030 slice has not been rerun.
- Browser/manual cashier edit-payment QA has not been run.

### Update 3 - Cashier edit calculator consumes backend payable context

Status: Fixed and locally verified for cashier edit payment calculator backend-payable consumption contract.

Scope:
- `app/Application/Note/Services/EditTransactionWorkspacePageDataBuilder.php`
- `resources/views/cashier/notes/workspace/partials/payment-modal.blade.php`
- `resources/views/cashier/notes/workspace/partials/payment-modal-right.blade.php`
- `public/assets/static/js/pages/cashier-note-workspace/payment-flow.js`
- `tests/Feature/Note/EditTransactionWorkspacePageFeatureTest.php`
- `tests/Feature/Note/CashierWorkspacePaymentFlowJavascriptContractTest.php`

Problem proven:
- The cashier edit workspace page did not render backend payment settlement explanation.
- The edit payment modal did not expose backend payable amount to the calculator surface.
- `payment-flow.js` did not consume the backend payable dataset exposed by the modal.
- The calculator path could keep deriving payable from visible workspace rows instead of backend outstanding settlement context.

RED proof 1 - backend explanation render:
- Test: `EditTransactionWorkspacePageFeatureTest::test_cashier_edit_workspace_renders_backend_payment_settlement_explanation_for_partially_paid_note`
- Scenario: existing note total `100000`, existing paid allocation `40000`, backend payable `60000`.
- Failure: rendered edit workspace did not contain `Settlement pembayaran backend`.
- Proof: `1 failed / 3 assertions`.

GREEN proof 1:
- Targeted proof: `1 passed / 6 assertions`.
- Focused edit workspace proof: `3 passed / 19 assertions`.

Patch 1:
- `EditTransactionWorkspacePageDataBuilder` now resolves backend payment settlement data for edit workspace.
- Edit payment modal UI now renders backend settlement explanation:
  - gross total
  - net paid
  - payable now

RED proof 2 - modal backend payable DOM contract:
- Test: `EditTransactionWorkspacePageFeatureTest::test_cashier_edit_workspace_exposes_backend_payable_amount_to_payment_calculator`
- Scenario: existing note total `100000`, existing paid allocation `40000`, backend payable `60000`.
- Failure: rendered modal did not contain `data-backend-payable-rupiah="60000"`.
- Proof: `1 failed / 3 assertions`.

GREEN proof 2:
- Targeted proof: `1 passed / 4 assertions`.
- Focused edit workspace proof after DOM patch: `4 passed / 23 assertions`.

Patch 2:
- `#workspace-payment-modal` now exposes:
  - `data-backend-payable-rupiah`
  - `data-backend-payment-basis`
- The data is derived from backend settlement resolver output, not JavaScript row totals.

RED proof 3 - JavaScript backend payable consumption:
- Test: `CashierWorkspacePaymentFlowJavascriptContractTest::test_payment_flow_consumes_backend_payable_dataset_for_edit_calculator`
- Failure: `payment-flow.js` did not contain `dataset.backendPayableRupiah`.
- Proof: `1 failed / 1 assertions`.

GREEN proof 3:
- Targeted JS contract proof: `1 passed / 3 assertions`.
- Combined edit workspace + JS contract proof: `5 passed / 26 assertions`.

Patch 3:
- `payment-flow.js` now reads backend payable dataset from the payment modal.
- It trusts backend payable only when `data-backend-payment-basis` is `backend_outstanding_settlement`.
- For edit/full mode, backend payable becomes the calculator payable target.
- Create mode remains compatible because no backend payable dataset is required.

Focused adjacency proof after JS patch:
- `CashierDetailRenderedBillingRowsPaymentFeatureTest`: `2 passed`.
- `CashierHybridPaymentSettleIntentFeatureTest`: `1 passed`.
- Combined adjacency proof: `3 passed / 14 assertions`.

Verification gaps:
- Full `make verify` has not been rerun after this cashier calculator consumption slice.
- Browser/manual cashier modal QA has not been run.
- Runtime JavaScript behavior is protected by static JS contract and focused PHP render tests, not by a browser-executed test.

### S12 - Surplus refund_paid carry-forward compatibility patch

Status: Fixed and locally verified.

Scope:
- Later revision after downward revision surplus refund payment.
- Active surplus refund_paid remains cash-out and must reduce net paid instead of becoming reusable available payment.
- Documentation only in this step. Production patch was already verified locally.

RED proof:
- Scenario: original paid 265.000; downward revision total 143.000; refund_due 122.000; active surplus refund_paid 50.000; later revision total 230.000.
- Expected settlement: carry_forward_paid_rupiah 265.000; carry_forward_refunded_rupiah 50.000; net_paid_rupiah 215.000; outstanding_rupiah 15.000; surplus_rupiah 0; settlement_status underpaid.
- Pre-patch actual incorrectly reclaimed refund_paid as available payment: carry_forward_refunded_rupiah 0; net_paid_rupiah 265.000; surplus_rupiah 35.000; settlement_status overpaid_pending.

Production files:
- app/Ports/Out/Note/NoteRevisionSurplusRefundPaymentReaderPort.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusRefundPaymentAdapter.php
- app/Adapters/Out/Note/DatabaseNoteRevisionSurplusRefundPaymentSumQuery.php
- app/Application/Note/Services/BuildCreateNoteRevisionSettlement.php

Test files:
- tests/Feature/Note/CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest.php
- tests/Unit/Application/Note/Services/BuildCreateNoteRevisionSettlementTest.php

Implementation decision:
- Add sumActiveAmountByNoteRootId(string $noteRootId): int to NoteRevisionSurplusRefundPaymentReaderPort.
- Delegate surplus refund payment sum reads from DatabaseNoteRevisionSurplusRefundPaymentAdapter to DatabaseNoteRevisionSurplusRefundPaymentSumQuery.
- BuildCreateNoteRevisionSettlement folds active surplus refund_paid by note root into carry_forward_refunded_rupiah.
- Formula: carry_forward_refunded_rupiah = ordinary/component refunded + active surplus refund_paid by note root.

Verification:
- php -l passed for all touched PHP files.
- Targeted feature test passed: 1 test / 3 assertions.
- Settlement builder unit test passed: 4 tests / 22 assertions.
- Focused blast-radius suite passed: 21 tests / 102 assertions.
- Full make verify passed: 1021 tests / 5485 assertions in 49.50s.
- Audit-lines blocker on DatabaseNoteRevisionSurplusRefundPaymentAdapter.php was fixed by extracting DatabaseNoteRevisionSurplusRefundPaymentSumQuery.php.

Semantic caveat:
- This is a compatibility patch. It folds surplus refund_paid into carry_forward_refunded_rupiah.
- Cleaner future schema may need explicit fields such as surplus_refund_paid_rupiah and remaining_refund_due_rupiah.
- Schema or migration work is intentionally out of scope for S12.

Locked non-goals:
- Do not merge revision submit and payment.
- Do not implement customer_credit, customer_balance_entries, PostgreSQL, Go API, or dashboard.
- Do not use customer_refunds or refund_component_allocations for surplus refund_paid.
- Do not trigger refunded lifecycle or inventory reversal for surplus refund_paid.

Remaining verification gaps:
- No schema split for explicit surplus_refund_paid_rupiah / remaining_refund_due_rupiah.
- No new production patch in this documentation step unless new regression proof appears.
