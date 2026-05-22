# Feature Continuation Blueprint

## Metadata

- Repo: `/home/asyraf/Code/laravel/bengkel2/app`
- Branch baseline when the blueprint was created: `audit-1461-selective-patch`
- Baseline HEAD: `c0ce90a6`
- Source context:
  - Audit 1461 selective patch is closed.
  - Cash payment detail has been persisted.
  - Push notification infrastructure already exists for due-note / customer-note reminders.
  - Supplier payable report already exists.
  - Dashboard operational performance already exists.
  - PDF / printed notes / reports are not yet in active scope.
- UI label stash outside audit:
  - `stash@{0}: temp-ui-refund-label-outside-audit`

## Locked Workflow

Every case must be handled one by one.

Required order:
1. Snapshot the repo.
2. Inspect related files.
3. Lock FACT, GAP, DECISION.
4. Prepare a minimum blueprint.
5. Implement a small patch.
6. Run focused tests.
7. Run `make verify`.
8. Make a small commit.
9. Create a case handoff in `docs/99_archive/handoff/v2/feature_continuation/`.
10. Update the status ledger in this file.

Do not increase progress without command-output proof.

Do not mix different features into one commit unless a blocking refactor, such as `audit-lines`, requires it.

Do not pop or stage the UI refund-label stash unless there is an explicit decision about UI wording.

## Priority Rules

### P0

Problems that can cause direct financial risk, late payment, wrong reports, wrong domain lifecycle behavior, or broken critical operational features.

P0 must be finished before P1 unless a technical blocker makes P1 a dependency.

### P1

Important operational problems that improve cashier/admin accuracy, reduce delay, or clarify the dashboard, but do not directly change the main financial lifecycle.

### P2

Enhancements, convenience, print/export, UI polish, or features whose contract has not yet been discussed enough.

P2 must not interfere with P0/P1.

## Status Ledger

| ID | Priority | Case | Status | Last Proof | Handoff |
|---|---:|---|---|---|---|
| FC-000 | P0 | System ambiguity inventory after abandoned feature work | CLOSED | Repo snapshot mapped cash change, dashboard, supplier payable notification, PDF, and UI stash ambiguity | `docs/99_archive/handoff/v2/feature_continuation/01-system-ambiguity-inventory.md` |
| FC-001 | P0 | Supplier payable push notification H-5 until paid off | OPEN | Snapshot found the supplier payable report and push infra, but no supplier payable push handler/command yet | Pending |
| FC-002 | P1 | Change-money potential on the monthly operational performance dashboard | OPEN | Snapshot found `change_rupiah`, but no related dashboard field/metric yet | Pending |
| FC-003 | P1 | Change-money denomination calculator | OPEN/PARTIAL | Cash change is persisted, but no denomination-calculator proof yet | Pending |
| FC-004 | P2 | PDF/printed notes/reports | OPEN | Snapshot only found supplier PDF attachment proof, not PDF generation for notes/reports | Pending |
| FC-005 | P2 | UI refund-label stash | DEFERRED | Stash still exists, outside the audit scope | Pending |

## FC-001 - Supplier Payable Push Notification H-5 Until Paid Off

### Priority

P0

### Problem

The system needs to send reminders or notifications if a supplier payable is approaching due date H-5, is already due, or remains unpaid until it is paid off.

### Known Facts

- `supplier_invoices` has `jatuh_tempo`.
- Supplier payable reporting already reads due date and outstanding balance.
- Push notification infrastructure already exists.
- The existing `push-notifications:send-due-note-reminders` command is for customer notes, not supplier payable.
- The existing due-note payload speaks about due notes, not supplier debt.

### Gaps

- There is no dedicated supplier payable reminder reader yet.
- There is no `SendSupplierPayableReminderPushHandler` use case yet.
- There is no supplier payable payload factory yet.
- There is no supplier payable reminder console command yet.
- There are no focused tests for H-5 through paid-off behavior.
- It is not decided whether notifications are sent daily or only when status changes.

### Required Contract

The reminder must target active supplier invoices with:
- `voided_at IS NULL`
- `jatuh_tempo <= today + 5 days`
- outstanding > 0
- still visible until outstanding becomes 0
- paid invoices do not appear
- voided invoices do not appear

### Suggested Implementation Plan

1. Inspect the supplier payable report query and the due-status resolver.
2. Add an application reader/use case or reuse the source reader if it is safe.
3. Add a supplier payable payload factory.
4. Add a supplier payable push handler.
5. Add a console command:
   - `push-notifications:send-supplier-payable-reminders`
6. Add tests:
   - H-6 is not sent.
   - H-5 is sent.
   - due today is sent.
   - overdue is sent.
   - fully paid is not sent.
   - voided is not sent.
   - expired push subscriptions are marked expired like the existing due-note flow.
7. Run focused push/procurement/reporting tests.
8. Run `make verify`.
9. Commit.
10. Create a handoff.

### Closure Proof Required

- Focused tests pass.
- `make verify` passes.
- Commit hash.
- Handoff file path.

## FC-002 - Change-Money Potential Dashboard Metric

### Priority

P1

### Problem

The admin dashboard section `Kinerja Operasional Bulan Ini` needs to be replaced or extended with a metric for change-money potential.

### Known Facts

- Cash payment detail is persisted in `customer_payment_cash_details`.
- Available fields:
  - `amount_paid_rupiah`
  - `amount_received_rupiah`
  - `change_rupiah`
- The operational performance dashboard already has a `Kinerja Operasional Bulan Ini` chart.
- There is no proof yet that the chart or dataset uses `change_rupiah`.

### Gaps

- The definition of "change-money potential" is not locked.
- It is not decided whether the metric is:
  - total `change_rupiah` for the month,
  - total cash received minus paid,
  - an estimated cash-drawer denomination breakdown,
  - or a minimum small-change recommendation.
- There is no dashboard test for this metric yet.

### Required Decision Before Patch

Choose one:
- Option A: show the monthly total `change_rupiah` as "Change Potential".
- Option B: show the total plus a denomination breakdown.
- Option C: the dashboard shows only the total, and the breakdown lives in a separate calculator.

### Suggested Default Decision

Option C.

Reason:
- The dashboard only needs to provide an indicator.
- Denominations are better suited to a dedicated calculator/helper.
- The dashboard UI stays less crowded.

### Closure Proof Required

- Dataset/read-model test.
- Dashboard page test.
- `make verify` passes.
- Commit hash.
- Handoff file path.

## FC-003 - Change-Money Denomination Calculator

### Priority

P1

### Problem

Cashier/admin users need a change-money denomination calculator so they can prepare small denominations practically.

### Known Facts

- The change amount is already calculated and persisted.
- There is no proof of a denomination-calculator implementation yet.

### Gaps

- The supported denominations are not decided yet.
- It is not decided whether the calculator is based on:
  - single-transaction change,
  - daily total,
  - monthly total,
  - or manual input.
- The UI location is not decided yet:
  - cashier payment modal,
  - admin dashboard,
  - or cash report page.

### Suggested Contract

Default denominations:
- 100000
- 50000
- 20000
- 10000
- 5000
- 2000
- 1000
- 500

Calculator harus deterministic:
- input integer rupiah
- output list pecahan dan count
- sisa tidak boleh negatif
- jika sisa tidak bisa dipecah oleh denom minimum, tampilkan remainder

### Suggested Implementation Plan

1. Buat pure service/value calculator kecil.
2. Unit test matrix.
3. Integrasi ke UI setelah pure logic locked.
4. Jika dipakai dashboard, ambil input dari aggregate `change_rupiah`.

### Closure Proof Required

- Unit tests denomination matrix.
- Feature/UI test jika di-render.
- `make verify` pass.
- Commit hash.
- Handoff file path.

## FC-004 - PDF/Cetak Nota/Laporan

### Priority

P2

### Problem

Ada kebutuhan cetak/PDF, tapi belum dibahas kontrak final.

### Known Facts

- Search menemukan PDF pada supplier payment proof attachment.
- Belum ada proof generate PDF nota/laporan.
- Belum ada keputusan library/rendering.

### Gaps

- Belum jelas PDF untuk:
  - nota pelanggan,
  - transaksi/kasus,
  - laporan profit,
  - supplier payable,
  - atau semua.
- Belum jelas output:
  - browser print,
  - generated PDF download,
  - stored PDF artifact,
  - atau template HTML printable.
- Belum jelas library:
  - dompdf/barryvdh,
  - browser print,
  - external renderer.

### Suggested Rule

Jangan mulai PDF sebelum P0 supplier payable notification dan P1 cash change dashboard/kalkulator jelas.

### Closure Proof Required

- Separate blueprint.
- Route/controller/view tests.
- Rendering smoke test.
- `make verify` pass.
- Commit hash.
- Handoff file path.

## FC-005 - UI Refund Label Stash

### Priority

P2

### Problem

Ada stash UI-only:
`Catat Refund / Batalkan Line` menjadi `Refund`.

### Known Facts

- Stash sengaja tidak dicampur ke audit 1461.
- Perubahan ini pernah menyebabkan test false negative karena expected label lama.

### Decision

Deferred.

Jangan pop sebelum ada keputusan UI wording dan update test terkait.

## Handoff Template

Setiap selesai satu kasus, buat file:

`docs/99_archive/handoff/v2/feature_continuation/YYYY-MM-DD-FC-XXX-short-name.md`

Template:

### Handoff FC-XXX - Title

## Metadata

- Branch:
- Start HEAD:
- End HEAD:
- Commit:
- Date:
- Scope:

## Final Decision

## Files Changed

## Tests / Proof

## What Was Closed

## What Was Not Closed

## Known Caveats

## Next Safe Step

## Opening Prompt For Next Session

Lanjutkan dari repo `/home/asyraf/Code/laravel/bengkel2/app`.

State terakhir:
- Branch:
- HEAD:
- Last commit:
- Pending:

Aturan:
- Zero assumption.
- Blueprint first.
- One active step.
- Jangan klaim progress tanpa command output.
- Jalankan snapshot dulu sebelum patch.
