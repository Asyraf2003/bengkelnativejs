# 001 - Refunds counted as paid in note totals

## Status

Patched.

## Severity

High.

## Source

Audit report #001: Refunds counted as paid in note totals.

## Ringkasan Indonesia

Bug terjadi pada kalkulasi total alokasi pembayaran level nota.

Method:

app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php
- getTotalAllocatedAmountByNoteId(string $noteId)

Sebelum patch, method tersebut menjumlahkan:

- payment_component_allocations.allocated_amount_rupiah
- refund_component_allocations.refunded_amount_rupiah

Kemudian hasilnya dikembalikan sebagai total allocated amount untuk nota.

Masalahnya, refund reader lain juga menghitung refund_component_allocations sebagai total refunded amount. Service downstream seperti paid status dan outstanding resolver memakai pola:

net_paid = allocated - refunded

Karena refund sudah ditambahkan ke allocated lalu dikurangi lagi sebagai refunded, refund aktif menjadi netral. Dengan kata lain, refund tidak benar-benar mengurangi net paid/outstanding.

## Contoh Dampak

Skenario:

- Total nota: 50.000
- Payment component: 50.000
- Refund component aktif: 10.000

Perhitungan yang benar:

- allocated = 50.000
- refunded = 10.000
- net_paid = 40.000
- outstanding = 10.000

Perhitungan saat bug:

- allocated = 60.000
- refunded = 10.000
- net_paid = 50.000
- outstanding = 0

Akibatnya nota bisa tetap dianggap lunas/closed walaupun ada refund aktif yang seharusnya membuka outstanding atau koreksi pembayaran.

## Jalur Risiko

Authenticated cashier/admin dapat membuat refund melalui route refund nota.

Refund tersebut membuat row pada refund_component_allocations.

Row refund yang sama kemudian:
1. ikut dihitung sebagai allocated amount oleh DatabasePaymentAllocationReaderAdapter
2. ikut dihitung sebagai refunded amount oleh refund reader

Paid-status dan outstanding resolver akhirnya menerima nilai settlement yang salah.

## Dampak Bisnis

Ini adalah financial-integrity issue.

Dampak utama:

- nota yang sudah direfund bisa tetap dianggap lunas
- outstanding bisa menjadi 0 padahal seharusnya masih ada
- follow-up payment atau correction flow bisa terblokir
- laporan/status nota bisa misleading
- audit settlement menjadi tidak akurat

Severity High tepat karena bug menyentuh uang, status nota, dan alur pembayaran. Tidak Critical karena membutuhkan authenticated role dan tidak melibatkan auth bypass, secret leak, code execution, atau cross-tenant takeover.

## Root Cause

Reader level nota mencampur dua konsep berbeda:

- allocated payment: uang yang dialokasikan dari pembayaran
- refunded amount: uang yang sudah dikembalikan/refund

Refund tidak boleh dihitung sebagai allocated payment umum pada reader ini, karena settlement aktif sudah punya jalur pengurangan refund sendiri.

## Patch Summary

Patch minimal diterapkan pada:

app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php

Perubahan:

- hapus query/agregasi refund_component_allocations dari getTotalAllocatedAmountByNoteId()
- ubah kondisi component-backed agar hanya melihat payment_component_allocations
- return hanya componentTotal untuk component-backed note
- fallback legacy payment_allocations tetap dipertahankan
- method getTotalAllocatedAmountByCustomerPaymentIdAndNoteId() tidak diubah

## Scope In

- Note-level allocated total calculation.
- Component-backed note payment allocation reader.
- Preservation of legacy payment_allocations fallback.

## Scope Out

- Customer-payment scoped allocation reader.
- Refund recording flow.
- Refund reader.
- Paid status policy.
- Outstanding resolver.
- Reports/exports/projections.
- Characterization/regression test creation.

## Proof Dari Patch Session

User reported these commands passed/executed:

- php -l app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php
- git status --short
- git diff -- app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php
- git add app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php && git commit -m "Fix note allocated total to exclude refunds"

Commit message:

Fix note allocated total to exclude refunds

Changed file:

app/Adapters/Out/Payment/DatabasePaymentAllocationReaderAdapter.php

Reported diff size:

+2
-6

## Remaining Follow-up

Recommended next audit/test step:

Create a characterization test for:

- note total 50.000
- component payment 50.000
- component refund 10.000
- expected allocated = 50.000
- expected refunded = 10.000
- expected net paid = 40.000
- expected outstanding = 10.000
- expected note is not treated as fully paid after active refund

This test should lock the settlement behavior so the same bug does not return later wearing a different hat, as bugs annoyingly enjoy doing.

## Related Follow-up Discovered Later

### Related Error Log

- 003-refunded-revised-notes-are-misclassified-as-underpaid.md

### Update

Update 2.

### Reason

A later audit report found a directly related but non-identical edge case in the same settlement area.

The patch for #001 removed refund_component_allocations from note-level allocated total to prevent active refunds from being counted as paid. Report #003 shows that this behavior can undercount revised notes where NoteReplacementPaymentAllocationReconciler has already rebuilt payment_component_allocations net-of-refund while historical refund_component_allocations remain.

This means #001 and #003 must be considered together before future settlement changes. A valid fix must preserve correct behavior for both:

1. active refund normal notes
2. revised notes with historical refunds already consumed during replacement/reconciliation

Do not solve one by blindly reverting into the other.

## Related Patch Update From Error Log 003

### Related Error Log

- 003-refunded-revised-notes-are-misclassified-as-underpaid.md

### Update

Update 3.

### Reason

A later patch for #003 re-added refund_component_allocations into DatabasePaymentAllocationReaderAdapter::getTotalAllocatedAmountByNoteId() to restore gross allocation basis for revised notes with historical refunds.

This is directly relevant to #001 because #001 was originally caused by active refunds being added into allocated totals and then subtracted again, making active refunds ineffective.

Future verification must prove both:

1. active refunds on normal notes reduce net paid/outstanding correctly
2. historical refunds on revised notes are not double-subtracted

If both are not tested, the fix may only move the settlement bug between #001 and #003.
