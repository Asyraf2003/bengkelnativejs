# 022 - Cashier refund route bypasses note access guard

## Status

Patched, with verification gap.

## Severity

High.

## Ringkasan

Route refund cashier dapat dipanggil tanpa melewati `EnsureCashierNoteAccess`.

Route `POST /cashier/notes/{noteId}/refunds` berada di dalam group cashier yang memiliki `auth`, `EnsureCashierAreaAccess`, `EnsureTransactionEntryAllowed`, dan `app.shell`, tetapi route tersebut berada di luar nested group `EnsureCashierNoteAccess`.

Akibatnya, cashier yang sudah login dan memiliki izin input transaksi dapat mengirim POST langsung ke endpoint refund untuk `Nota` closed yang seharusnya tidak bisa diakses melalui area cashier, termasuk nota historical di luar window akses normal, selama attacker mengetahui `noteId` dan `customer_payment_id`.

## Jalur rentan

Authenticated cashier session
-> submit POST `/cashier/notes/{noteId}/refunds`
-> route melewati auth, cashier-area, dan transaction-entry
-> route tidak melewati `EnsureCashierNoteAccess`
-> `RecordClosedNoteRefundRequest::authorize()` mengizinkan caller yang sudah mencapai route
-> controller hanya mengecek note ada dan operationally closed
-> controller meneruskan payment id, amount, date, reason ke refund handler
-> handler membatasi nominal berdasarkan allocation/payment rules
-> refund tetap tercatat pada note yang tidak boleh diakses cashier

## Root cause

Guard akses per-nota sudah ada, tetapi route refund cashier ditempatkan di luar group `EnsureCashierNoteAccess`.

Controller dan request juga tidak melakukan pengganti validasi akses note/date-window. Karena itu, boundary akses nota cashier tidak diterapkan pada endpoint mutasi refund.

## Patch summary

`routes/web/note.php` diubah agar route berikut masuk ke dalam group `EnsureCashierNoteAccess`:

`Route::post('/{noteId}/refunds', RecordClosedNoteRefundController::class)->name('refunds.store');`

Dengan patch ini, endpoint refund cashier memakai guard akses nota yang sama seperti route cashier lain untuk show, workspace update, rows, payments, dan correction routes.

## Verification

Reported patch commit:

`6f3f2e1 - Protect cashier refund route with note access middleware`

Reported test attempt:

`php artisan test --filter=RecordClosedNoteRefundControllerFeatureTest`

Result:

Failed because `vendor/autoload.php` was not present in the environment.

## Verification gap

Framework test belum terbukti pass karena dependency runtime tidak tersedia.

Future verification:

- `php artisan route:list --path=cashier/notes`
- pastikan `cashier.notes.refunds.store` berada di bawah `EnsureCashierNoteAccess`
- `php artisan test --filter=RecordClosedNoteRefundControllerFeatureTest`
- test unauthorized historical/closed note refund harus ditolak sebelum handler refund membuat mutation record

## Relations

Direct follow-up to #021.

#021 covers missing whole-note closed invariant in the refund controller.

#022 covers missing cashier note-access guard on the refund route.

Related to #019 because #019 covers cashier historical closed note disclosure through the table route, while #022 covers unauthorized refund mutation against closed/historical notes if identifiers are known.

Related to #014 because both issues are in the refund endpoint policy cluster.

Related to #018 because refund lifecycle state must remain protected by cashier closed-note and terminal-state guards.
