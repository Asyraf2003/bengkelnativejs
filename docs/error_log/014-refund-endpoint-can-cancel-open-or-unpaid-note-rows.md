# 014 - Refund endpoint can cancel open or unpaid note rows

## Status

Patched, with verification gap.

Patch supplied and feature tests updated, but tests could not run in the patch environment because vendor/autoload.php / dependencies were missing.

## Severity

High.

## Source

Audit report #014: Refund endpoint can cancel open or unpaid note rows.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 013-forged-row-refund-can-auto-finalize-unpaid-notes.md
- 012-canceled-note-rows-re-enter-payment-flows.md

### Jenis Keterkaitan

Direct follow-up / residual issue from #013.

Direct row-state validation relationship with #012.

### Alasan

Laporan #014 sangat berkaitan dengan #013, tetapi tidak identik.

- #013 membahas zero-allocation selected-row refund yang bisa auto-finalize note unpaid menjadi refunded.
- #014 membahas akar validasi yang lebih awal: refund endpoint menerima open/unpaid rows dan tetap membatalkannya, sehingga note total turun walau row belum layak refund.

Patch #013 mencegah auto-finalization ketika allocation_count = 0, tetapi masih menyisakan risiko row unpaid/open bisa dibatalkan lewat refund endpoint.

Patch #014 memperbaiki residual risk tersebut dengan mengembalikan precondition bahwa selected row harus operationally close sebelum boleh masuk refund/cancel flow.

Laporan #014 juga berkaitan dengan #012 karena sama-sama menunjukkan row-level flows wajib membedakan status row secara eksplisit sebelum payment/refund mutation.

## Update Log

### Update 1

Initial audit log entry untuk laporan #014.

Alasan update:

- Laporan menunjukkan refund endpoint bisa cancel open/unpaid note rows.
- Patch menambahkan strict operationally close precondition pada SelectedNoteRowsRefundPlanResolver.
- Open/unpaid rows sekarang fail-fast dengan refund validation error.
- Verification masih gap karena test gagal dijalankan akibat missing dependencies.

## Ringkasan Indonesia

Bug terjadi pada selected-row refund flow.

Sebelum patch, controller refund memakai plan-based flow:

RecordClosedNoteRefundController
-> SelectedNoteRowsRefundPlanResolver
-> RecordSelectedRowsRefundPlanTransaction
-> CancelSelectedRowsAndSyncActiveNoteTotal

Resolver hanya mengecek:

- selected row ada di note
- selected row belum inactive/refunded

Resolver tidak memastikan row tersebut sudah operationally close / paid / refundable.

Akibatnya authenticated cashier/admin bisa mengirim selected_row_ids untuk row yang masih open atau unpaid. Transaction flow tetap memproses bucket yang ada, lalu membatalkan semua selected rows dan menghitung ulang active note total.

Untuk partially paid row:

- hanya paid portion yang mungkin direfund
- seluruh row tetap dicancel
- unpaid portion hilang dari active note total

Untuk fully unpaid row:

- payment bucket kosong
- tidak ada refund allocation
- row tetap bisa dicancel
- note total tetap turun

## Dampak

Dampak utama:

- cashier/admin bisa membatalkan active/open rows lewat refund endpoint
- unpaid row bisa dihapus dari active note total
- partially paid row bisa dicancel penuh walau refund hanya sebagian
- receivable/sales bisa turun tanpa refund yang valid
- work item integrity rusak
- financial note total bisa dimanipulasi
- inventory/accounting consistency bisa ikut terdampak untuk store-stock rows

Severity High tepat karena endpoint refund dapat dipakai untuk mengubah financial/work-item state tanpa memenuhi invariant paid/refundable row. Tidak Critical karena membutuhkan authenticated cashier/admin, valid session/CSRF, dan target note/row IDs.

## Jalur Risiko

Workflow risiko:

1. User login sebagai cashier/admin.
2. User mengirim POST ke cashier.notes.refunds.store.
3. Request berisi selected_row_ids milik row open/unpaid.
4. RecordClosedNoteRefundController memanggil SelectedNoteRowsRefundPlanResolver.
5. Resolver lama menerima row karena row valid dan belum inactive/refunded.
6. Payment buckets bisa kosong atau hanya sebagian.
7. RecordSelectedRowsRefundPlanTransaction tetap memanggil cancellation.
8. CancelSelectedRowsAndSyncActiveNoteTotal mengubah selected rows menjadi CANCELED.
9. Note active total dihitung ulang dari remaining rows.
10. Open/unpaid work item hilang dari active note total lewat refund endpoint.

## Root Cause

Root cause:

Refund plan resolver tidak menegakkan invariant bahwa selected row harus operationally close sebelum refund.

Sebelum plan-based flow, selected-row refund amount resolver menolak row yang operational status-nya bukan CLOSE.

Setelah flow diganti ke SelectedNoteRowsRefundPlanResolver, precondition itu hilang.

Akibatnya refund endpoint berubah menjadi generic row cancellation endpoint, padahal secara domain refund seharusnya hanya berlaku pada row yang sudah paid/closed/refundable.

## Patch Summary

Patch diterapkan pada:

app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php

Perubahan:

- menambahkan helper:
  isOperationallyClose(WorkItem $item, array $settlement): bool
- helper menghitung:
  - refunded_rupiah
  - outstanding_rupiah
  - operational status via WorkItemOperationalStatusResolver
- selected row sekarang wajib menghasilkan status CLOSE
- jika tidak operationally close, resolver return failure:
  Line open/belum lunas tidak boleh direfund.
- existing inactive/refunded check tetap dipertahankan
- downstream plan/bucket behavior tetap dipertahankan untuk row yang legitimate refundable

Test diubah pada:

tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php
tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php

Test intent:

- refund operationally open line harus ditolak
- no refund allocations created
- row status tetap open
- note total tetap unchanged

## Scope In

- Selected-row refund resolver.
- Operationally close precondition.
- Rejection of open/unpaid rows in refund endpoint.
- Preventing refund endpoint from acting as cancellation endpoint for unpaid rows.
- Feature test updates for open-line rejection.

## Scope Out

- Auto-finalization guard from #013.
- Canceled-row payment/status issue from #012.
- Inventory reversal details.
- Full browser E2E.
- UI changes for hiding refund launcher.
- Broader refund/cancellation domain redesign.
- Production data cleanup for rows already canceled through old vulnerable flow.

## Proof Dari Patch Session

User reported:

- vulnerability still present in HEAD
- minimal remediation implemented in existing refund-plan path
- strict operationally close precondition restored for each selected row
- open/unpaid rows fail fast with validation-style refund error
- helper isOperationallyClose() added
- existing inactivity/refunded checks preserved
- committed on current branch:
  5dd4e7b
- PR metadata created with title:
  Fix cashier refund flow to reject open/unpaid selected rows

Changed files:

app/Application/Note/Services/SelectedNoteRowsRefundPlanResolver.php
tests/Feature/Note/CashierRefundRejectsOpenLineFeatureTest.php
tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php

Reported diff size:

+22
-23

Testing attempted:

php artisan test --filter='CashierRefundRejectsOpenLineFeatureTest|RecordSelectedRowsClosedNoteRefundHttpFeatureTest|RecordClosedNoteRefundControllerFeatureTest'

Result:

Failed due to environment limitation.

Failure reason:

vendor/autoload.php / dependencies not installed.

## Verification Gap

Tests were updated but did not pass in the patch environment.

Missing proof:

- open unpaid row refund request is rejected
- partially paid but operationally open row refund request is rejected
- no refund_component_allocations created
- row status remains OPEN
- note total remains unchanged
- legitimate operationally close selected row refund still succeeds
- interaction with #013 finalization guard remains correct

## Recommended Follow-up

Minimum verification command:

composer install
php artisan test --filter='CashierRefundRejectsOpenLineFeatureTest|RecordSelectedRowsClosedNoteRefundHttpFeatureTest|RecordClosedNoteRefundControllerFeatureTest'

Recommended extra tests:

1. Fully unpaid open row selected:
   - expected refund error
   - no row cancellation
   - no note total change

2. Partially paid operationally open row selected:
   - expected refund error
   - no partial refund
   - no full cancellation

3. Fully paid/close row selected:
   - expected refund success
   - refund allocation created
   - row canceled/refunded according to intended domain behavior

4. Zero-allocation selected-row refund:
   - should not finalize note as refunded, matching #013

## Kesimpulan

Laporan #014 valid sebagai High severity financial/work-item integrity issue.

Bug sebelumnya membuat refund endpoint menerima open/unpaid rows dan membatalkannya, sehingga active note total bisa turun tanpa refund yang valid. Patch #013 hanya menahan auto-finalization, tetapi belum menutup akar validasi row refund.

Patch #014 mengarah benar karena mengembalikan invariant: hanya row operationally close yang boleh masuk selected-row refund flow. Namun test belum terbukti pass karena dependency environment belum tersedia.

## Related Refunded-State Terminal Guard Finding From Error Log 018

### Related Error Log

- 018-refunded-notes-bypass-cashier-closed-note-guards.md

### Update

Update 2.

### Reason

A later audit report found a separate issue in refunded-note lifecycle enforcement.

This is not the same root cause as #014.

- #014 is about preventing open/unpaid rows from entering refund/cancel flow.
- #018 is about preventing refunded notes from being mutated after full refund.

Both are required for safe refund lifecycle enforcement.

## Related #021 - Refunds can be recorded on open notes

#021 is a direct follow-up in the refund endpoint policy cluster. #014 covers selected rows that are open/unpaid being accepted by refund validation. #021 covers selected rows that are close but belong to a parent `Nota` that is still open. The safe invariant is that refund mutation requires whole-note close status, not only selected-row close status.

## Related #022 - Cashier refund route bypasses note access guard

#022 is related through the refund endpoint policy cluster. #014 covers invalid row eligibility in refund validation, while #022 covers missing note-level cashier access middleware on the refund route itself.
