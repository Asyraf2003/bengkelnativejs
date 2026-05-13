# Handoff 0005 - Reporting Downward Surplus Characterization

## Metadata

- Date: 2026-05-13
- Sequence: 0005
- Scope: reporting downward surplus characterization
- Previous handoff: docs/99_archive/handoff/v2/edit_refund_sniper/0004_baseline_policy_hardening_handoff.md
- Latest proof: targeted reporting characterization passed; broader test summary passed.

## Status

test-only characterization

## Session Goal

Verify whether transaction summary reporting needs a production patch after Phase 1C-B downward surplus revision commit.

The goal was to prove whether reporting reads capped payment allocations or gross customer payment amount after a downward paid revision leaves surplus as overpaid_pending.

## Facts

- Phase 1A and Phase 1B were already completed before this session.
- Phase 1C-B downward surplus revision commit was already completed before this session.
- note_revision_settlements records downward surplus as overpaid_pending.
- Downward surplus remains pending undecided money state.
- Surplus is not revenue.
- Surplus is not automatic refund.
- Surplus is not automatic customer credit.
- customer_balance_entries remains deferred until surplus disposition is decided.
- UI is not financial truth.
- No ledger or history rewrite is allowed.
- No generic reader or query patch is allowed without consumer proof.
- Source audit showed transaction summary reporting normal path reads allocated payment from payment_allocations.amount_rupiah.
- Source audit showed the refund fallback path can read customer_payments.amount_rupiah only when no payment allocation exists for the refund payment and note.
- Source audit showed TransactionSummaryPerNoteBuilder and TransactionPaymentStatusLabelResolver trust the raw rows from reporting source.
- A test-only characterization was added for downward surplus reporting normal path.
- The characterization proves reporting uses capped payment allocation amount instead of gross customer payment amount.

## Gaps

Blocks next step:

- None for transaction summary reporting normal path.

Does not block next step:

- Refund fallback path remains legacy/refund-specific and was not changed in this slice.
- Surplus disposition remains undecided.
- customer_balance_entries remains deferred.
- No browser/manual QA was performed in this slice.
- No UI proof was required because UI is not financial truth and was out of scope.

## Assumptions

No production implementation assumption accepted.

The test fixture represents the reporting-normal-path condition where:

- note total is 143000
- customer payment gross is 265000
- payment allocation is capped at 143000
- there is no refund
- downward surplus remains outside reporting as pending undecided money state

## Decisions

- Decision source: source audit.
  - Transaction summary reporting normal path already reads payment_allocations amount.
- Decision source: targeted proof.
  - No production reporting patch is needed for downward surplus normal path.
- Decision source: locked domain decision.
  - overpaid_pending must not be treated as revenue, refund, or customer credit.
- Decision source: scope policy.
  - No UI, controller, generic query, or settlement-reader consumer was added.

## Active Slice

Selected active slice:

Reporting Downward Surplus Characterization.

Scope in:

- transaction summary reporting query characterization
- capped allocation versus gross customer payment proof
- builder/status output proof from raw reporting rows
- handoff update

Scope out:

- app production code
- database schema
- UI
- controller
- generic reporting query patch
- note revision settlement reader consumer
- customer balance disposition
- refund disposition
- ledger/history rewrite

Files touched:

- tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php
- docs/99_archive/handoff/v2/edit_refund_sniper/README.md
- docs/99_archive/handoff/v2/edit_refund_sniper/0005_reporting_downward_surplus_characterization_handoff.md

Files not touched:

- app/*
- database/*
- resources/*
- public/*

DB impact:

- None in production.
- Test fixture only.

UI impact:

- None.

Report impact:

- No production report code changed.
- Existing report behavior is now characterized for downward surplus normal path.

API impact:

- None.

Audit impact:

- Added regression proof that downward surplus reporting normal path uses capped allocations.

## Source Audit Summary

Source audited:

- path: app/Adapters/Out/Reporting/Queries/TransactionSummaryReportingQuery.php
  - relevant behavior: gross comes from notes.total_rupiah
  - relevant behavior: allocated_payment_rupiah normal path comes from SUM(payment_allocations.amount_rupiah)
  - risk checked: report could read gross customer_payments.amount_rupiah instead of capped allocation
  - result: normal path reads capped payment allocation

- path: app/Application/Reporting/Services/TransactionSummaryPerNoteBuilder.php
  - relevant behavior: maps raw reporting rows into TransactionSummaryPerNoteRow
  - result: builder trusts raw rows and does not change allocated amount

- path: app/Application/Reporting/Services/TransactionPaymentStatusLabelResolver.php
  - relevant behavior: status depends on gross, allocated, refunded
  - result: status is safe when raw allocated is capped

- path: app/Application/Reporting/DTO/TransactionSummaryPerNoteRow.php
  - relevant behavior: outstanding is gross minus allocated plus refunded
  - result: DTO is safe when raw allocated is capped

- path: tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php
  - relevant behavior: existing reporting query feature tests and helpers were available
  - result: best location for characterization test

## Files Changed

- tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php
  - Added test_downward_revision_surplus_reporting_uses_capped_allocations_not_customer_payment_gross.

- docs/99_archive/handoff/v2/edit_refund_sniper/0005_reporting_downward_surplus_characterization_handoff.md
  - Added this handoff.

- docs/99_archive/handoff/v2/edit_refund_sniper/README.md
  - Latest Handoff pointer updated to this file.

## Tests And Proof

Targeted characterization command:

    php artisan test tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php --filter=downward_revision_surplus_reporting_uses_capped_allocations_not_customer_payment_gross

Result:

    PASS Tests\Feature\Reporting\TransactionSummaryReportingQueryFeatureTest
    downward revision surplus reporting uses capped allocations not customer payment gross
    1 passed, 13 assertions

Broader proof from owner output:

    973 passed, 5169 assertions

Interpretation:

- Reporting normal path reads capped allocation amount 143000.
- Reporting normal path does not read gross customer payment 265000 as allocated amount.
- Derived row output has outstanding_rupiah 0.
- Derived row output has payment_status_label Lunas.
- No production reporting patch is required for this case.

## Residual Risks

Blocks next step:

- None.

Does not block next step:

- overpaid_pending disposition remains undecided.
- customer balance lifecycle remains deferred.
- refund fallback legacy behavior was not changed.
- no browser/manual QA was performed.
- no UI change was required.

Needs owner decision:

- What exact operation will later dispose overpaid_pending:
  - refund due
  - customer credit
  - owner-retained balance
  - other explicit business decision

Future improvement:

- Add a later disposition lifecycle only after owner decision.
- Do not add customer_balance_entries until surplus disposition is locked.

## Next Active Step

Goal:

Continue to the next scoped post-Phase 1C-B boundary without reopening completed reporting normal-path work.

Recommended next target:

Audit customer-facing surplus disposition boundary at the decision level only.

Scope:

- identify where overpaid_pending could later become refund due, customer credit, or retained balance
- do not implement customer_balance_entries yet
- do not patch UI
- do not patch reporting normal path
- do not rewrite ledger/history

Command if needed:

    grep -RIn -e "customer_balance" -e "credit" -e "overpaid_pending" -e "refund due" -e "refund_due" app database docs/03_blueprints docs/02_architecture/adr tests | sed -n '1,240p'

Expected proof:

- current source/docs inventory for surplus disposition concepts
- clear GAP if business disposition remains undecided

Stop condition:

- stop before implementation if surplus disposition is not locked by owner decision.

## README Update Required

Yes.

README latest handoff pointer must point to:

    docs/99_archive/handoff/v2/edit_refund_sniper/0005_reporting_downward_surplus_characterization_handoff.md

## Session Context Health

49 percent.

Reason:

This session added a reporting characterization test and locked a no-production-patch decision for downward surplus transaction summary reporting normal path.
