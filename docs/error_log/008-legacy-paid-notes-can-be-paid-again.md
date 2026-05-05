# 008 - Legacy-paid notes can be paid again

## Status

Patched, with verification gap.

Patch supplied and regression test added, but tests could not run in the patch environment because phpunit/vendor dependencies were missing.

## Severity

High.

## Source

Audit report #008: Legacy-paid notes can be paid again.

## Relasi Dengan Error Log Lain

### Berkaitan Dengan

- 005-note-revision-silently-drops-overpaid-allocations.md
- 003-refunded-revised-notes-are-misclassified-as-underpaid.md

### Jenis Keterkaitan

Indirect financial/payment allocation relationship.

### Alasan

Laporan #008 berada pada area payment allocation and settlement integrity.

- #005 membahas payment replay saat note revision yang bisa menyembunyikan overpaid excess.
- #003 membahas mixed net/gross settlement pada revised note dengan historical refund.
- #008 membahas selected-row payment validation yang mengabaikan legacy payment_allocations sehingga legacy-paid note bisa dibayar ulang.

Ketiganya menyangkut settlement basis yang harus konsisten, tetapi #008 bukan bug yang sama karena route, service, dan precondition berbeda.

#008 terjadi pada selected-row payment flow untuk note legacy-paid, bukan pada note revision replay.

## Update Log

### Update 1

Initial audit log entry untuk laporan #008.

Alasan update:

- Laporan menunjukkan legacy-paid notes dapat dibayar ulang karena selected-row resolver memakai component-only billing projection.
- Patch sudah diterapkan di SelectedNoteRowsPaymentAmountResolver.
- Regression test ditambahkan untuk fully paid legacy note.
- Verification masih gap karena test gagal dijalankan akibat missing dependencies.

## Ringkasan Indonesia

Bug terjadi pada selected-row payment validation.

Sebelum bug, selected-row payment validation memakai path settlement yang legacy-aware. Path itu bisa fallback ke:

- payment_allocations
- customer_refunds

jika component allocations belum ada.

Commit yang dilaporkan mengubah resolver agar memakai NoteBillingProjectionBuilder secara langsung.

Masalahnya, NoteBillingProjectionBuilder hanya menghitung:

- payment_component_allocations
- refund_component_allocations

dan tidak fallback ke legacy:

- payment_allocations
- customer_refunds

Akibatnya, note yang sebenarnya sudah lunas lewat legacy payment_allocations dapat terlihat masih outstanding penuh jika component allocation tables kosong.

Seorang authenticated cashier/admin bisa mengirim selected_row_ids valid ke endpoint payment, lalu sistem menerima pembayaran baru untuk note yang sebenarnya sudah paid.

## Dampak

Dampak utama:

- legacy-paid note bisa dibayar ulang
- customer_payments baru bisa tercatat
- payment_component_allocations baru bisa dibuat untuk note yang sudah settled
- cash ledger/reporting bisa corrupt
- customer billing state menjadi tidak akurat
- payment integrity rusak, terutama untuk migrated/legacy notes

Severity High tepat karena ini menyentuh payment records dan financial reporting. Tidak Critical karena membutuhkan authenticated cashier/admin, valid CSRF/session, accessible note, dan kondisi data legacy tanpa component allocations.

## Jalur Risiko

Workflow risiko:

1. User login sebagai cashier/admin.
2. Target note sudah dibayar via legacy payment_allocations.
3. Note belum memiliki payment_component_allocations.
4. User submit POST payment endpoint dengan selected_row_ids valid.
5. SelectedNoteRowsPaymentAmountResolver membaca billing rows dari NoteBillingProjectionBuilder.
6. NoteBillingProjectionBuilder hanya melihat component allocations.
7. Legacy payment_allocations diabaikan.
8. Outstanding row terlihat masih penuh.
9. Resolver menerima payment.
10. RecordAndAllocateNotePaymentOperation juga melakukan check dengan component-only allocation reader.
11. Sistem membuat customer_payments dan payment_component_allocations baru.
12. Note yang sudah settled menjadi punya duplicate/excess payment records.

## Root Cause

Root cause:

Selected-row payment validation memakai projection yang tidak memiliki legacy fallback.

Ada dua settlement basis yang tidak konsisten:

1. Workspace operational settlement path:
   - component-aware
   - legacy-aware fallback
   - bisa melihat legacy payment_allocations

2. Direct billing projection path:
   - component-only
   - tidak melihat legacy payment_allocations

Bug muncul karena selected-row resolver pindah dari basis #1 ke basis #2.

Dalam sistem yang masih punya data legacy, component-only projection tidak boleh menjadi source of truth tunggal untuk outstanding validation.

## Patch Summary

Patch diterapkan pada:

app/Application/Note/Services/SelectedNoteRowsPaymentAmountResolver.php

Perubahan:

- tambahkan dependency NoteWorkspacePanelDataBuilder
- build workspace panel untuk note
- jika workspace null, return PAYMENT_INVALID_TARGET
- billing rows dibangun dari legacy-aware workspace rows:
  NoteBillingProjectionBuilder::buildFromWorkspaceRows((array) ($workspace['rows'] ?? []))
- direct call ke NoteBillingProjectionBuilder::build($note->id()) tidak lagi menjadi basis utama selected-row validation

Efek patch:

- selected-row payment amount validation kembali memakai settlement rows yang legacy-aware
- fully paid legacy note menghasilkan outstanding 0
- duplicate payment untuk legacy-paid note harus ditolak

Test ditambahkan pada:

tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php

Test baru:

test_rejects_selected_row_payment_when_note_already_paid_via_legacy_allocation

Test intent:

- seed note dengan total 50.000
- seed work item service 50.000
- seed customer_payments 50.000
- seed legacy payment_allocations 50.000
- submit cashier selected-row payment
- expect session error payment
- expect customer_payments count tetap 1
- expect payment_component_allocations count tetap 0

## Scope In

- SelectedNoteRowsPaymentAmountResolver.
- Selected-row payment amount validation.
- Legacy-paid note duplicate payment prevention.
- Legacy-aware workspace row settlement reuse.
- Regression coverage for legacy payment_allocations.

## Scope Out

- Full migration from legacy payment_allocations to component allocations.
- Reporting query redesign.
- RecordAndAllocateNotePaymentOperation component-only check beyond this resolver.
- Full browser E2E.
- Production data audit for legacy-paid note count.
- Cleanup of old legacy allocation model.
- Payment replay issues from #005.
- Refund/revised note settlement issue from #003.

## Proof Dari Patch Session

User reported:

- vulnerability still existed in current HEAD
- minimal fix implemented in selected-row resolver
- settlement input switched from direct component projection to legacy-aware workspace rows
- regression feature test added
- committed on branch work with commit:
  c8938eb
- PR record created with title:
  Fix duplicate payments for legacy-paid selected billing rows

Changed files:

app/Application/Note/Services/SelectedNoteRowsPaymentAmountResolver.php
tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php

Reported diff size:

+74
-1

Testing reported:

./vendor/bin/phpunit --filter RecordNotePaymentHttpFeatureTest

Result:

Failed because ./vendor/bin/phpunit not found.

php artisan test --filter=RecordNotePaymentHttpFeatureTest

Result:

Warning/failure because vendor/autoload.php not present.

## Verification Gap

Regression test was added but did not pass in the patch environment.

Missing proof:

- test_rejects_selected_row_payment_when_note_already_paid_via_legacy_allocation passes
- selected-row payment is rejected for legacy-paid note
- no extra customer_payments row created
- no payment_component_allocations row created
- normal component-backed payment flow still works
- mixed legacy/component notes behave according to intended migration rules

## Recommended Follow-up

Minimum verification commands:

composer install
php artisan test --filter=RecordNotePaymentHttpFeatureTest

Recommended additional tests:

1. Legacy fully paid note:
   - payment_allocations = total
   - component allocations empty
   - selected-row payment rejected

2. Legacy partially paid note:
   - payment_allocations less than total
   - selected-row payment accepted only up to remaining outstanding

3. Component-paid note:
   - component allocations exist
   - existing component behavior unchanged

4. Mixed legacy + component note:
   - define expected migration behavior before patching further

## Kesimpulan

Laporan #008 valid sebagai High severity financial-integrity issue.

Bug sebelumnya membuat selected-row payment validation memakai component-only billing projection. Untuk note yang sudah paid melalui legacy payment_allocations, sistem bisa menganggap outstanding masih penuh dan menerima pembayaran baru.

Patch mengarah benar karena mengembalikan selected-row validation ke legacy-aware workspace settlement rows. Namun karena test belum bisa dijalankan, status tetap patched with verification gap. Sekali lagi: test yang ditulis tapi tidak jalan itu bukan proof, cuma niat baik dalam format PHP.

## Related Payment Allocation Concurrency Finding From Error Log 010

### Related Error Log

- 010-revision-reallocation-can-lose-concurrent-payments.md

### Update

Update 2.

### Reason

A later audit report found a separate High severity payment allocation issue.

This is not the same root cause as #008.

- #008 is about selected-row payment validation ignoring legacy payment_allocations.
- #010 is about lost updates between concurrent payment recording and note revision allocation rebuild.

Both findings affect payment allocation integrity and prove payment state must be validated against the correct settlement basis and protected against unsafe interleavings.
