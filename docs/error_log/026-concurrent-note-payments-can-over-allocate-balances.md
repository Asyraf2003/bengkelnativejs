# 026 - Concurrent note payments can over-allocate balances

## Status

Patched, with verification gap.

## Keparahan

High.

## Ringkasan

Payment allocation rentan race condition.

Route `POST /cashier/notes/{noteId}/payments` dapat menerima dua request paralel untuk nota dan row yang sama. Sebelum patch, flow payment membaca total allocated saat ini dengan query `SUM` biasa, lalu menjalankan `PaymentAllocationPolicy`, lalu menulis `customer_payment` dan allocation record.

Karena tidak ada row lock, idempotency token, serializable isolation, atau constraint database yang menjaga aggregate allocation tidak melebihi total nota, dua transaction paralel dapat membaca allocated total yang sama, sama-sama lolos validasi outstanding, lalu sama-sama commit.

Dampaknya: total payment allocation dapat melebihi total `Nota`, merusak receivable, cash ledger, report, dan rekonsiliasi.

## Jalur rentan

Authenticated cashier
-> mengirim dua POST paralel ke `/cashier/notes/{noteId}/payments`
-> controller resolve selected-row amount tanpa idempotency guard
-> handler mulai transaction
-> handler membaca current allocated total dengan non-locking SUM
-> dua request membaca stale total yang sama
-> dua request lolos `PaymentAllocationPolicy`
-> dua request insert payment/allocation
-> total allocation melebihi total nota
-> financial state corrupt

## Root cause

Proteksi over-allocation bergantung pada hasil read current allocated total, tetapi read tersebut tidak diserialisasi terhadap write allocation berikutnya.

`PaymentAllocationPolicy` benar secara single-request, tetapi tidak cukup untuk concurrent requests jika nilai `totalAllocatedByNoteRupiah` berasal dari pembacaan non-locking.

## Patch summary

`app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php` diubah agar `execute()` melakukan note-level row lock sebelum membaca allocation dan menulis payment/allocation.

Patch behavior:

- normalize `noteId`
- lock row `notes` dengan `lockForUpdate()`
- reload note setelah lock
- lanjutkan policy check dan allocation write di bawah lock yang sama

Dengan lock per nota, dua request payment untuk nota yang sama harus berjalan serial, sehingga request kedua membaca allocated total terbaru setelah request pertama commit.

## Verification

Reported failed check:

`php artisan test tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php`

Failure reason:

`vendor/autoload.php` missing.

## Verification gap

Framework feature test belum terbukti pass karena dependency runtime tidak tersedia.

Future verification:

- install dependencies
- run `php artisan test tests/Feature/Note/RecordNotePaymentHttpFeatureTest.php`
- tambahkan/cek concurrency test dengan dua request paralel untuk nota dan row yang sama
- pastikan hanya satu request yang berhasil ketika combined amount akan melebihi total nota
- pastikan request kedua membaca allocation terbaru setelah lock dilepas
- pastikan lock berada di dalam transaction yang sama dengan allocation read dan write

## Residual risk

`lockForUpdate()` hanya efektif jika dijalankan di dalam database transaction yang sama dengan read allocation dan write allocation.

Jika operation ini kelak dipanggil di luar transaction wrapper, lock tidak cukup sebagai proteksi concurrency. Contract operation harus tetap memastikan note lock, allocated-total read, policy check, payment write, allocation write, auto-close, dan audit berjalan dalam satu transaction boundary.

Idempotency key untuk form submission masih dapat ditambahkan sebagai defense-in-depth, tetapi lock per nota adalah kontrol minimum untuk menjaga invariant allocation tidak melebihi total nota.

## Relations

Related to #010.

#010 covers revision/payment concurrency where a revision flow can lose concurrent payment allocations.

#026 covers payment/payment concurrency where two concurrent payment requests can over-allocate the same note.

Related to #008 because both issues break the payment allocation invariant and can allow overpayment or incorrect outstanding/payment totals, but #008 is caused by mixed legacy/component allocation sources while #026 is caused by non-serialized concurrent writes.
