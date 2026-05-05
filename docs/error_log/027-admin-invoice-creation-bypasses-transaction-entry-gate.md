# 027 - Admin invoice creation bypasses transaction-entry gate

## Status

Patched, with verification gap.

## Keparahan

High.

## Ringkasan

Route pembuatan supplier invoice admin melewati gate `transaction.entry`.

Endpoint `POST /admin/procurement/supplier-invoices` berada di group middleware:

- `web`
- `auth`
- `admin.page`
- `app.shell`

Namun route tersebut sebelumnya tidak memakai `transaction.entry` atau check capability setara.

`admin.page` hanya membuktikan actor memiliki role admin. Gate itu tidak membuktikan admin boleh melakukan input transaksi. Admin dengan transaction-entry capability inactive tetap dapat membuat supplier invoice melalui route ini.

## Jalur rentan

Admin login
-> submit `POST /admin/procurement/supplier-invoices`
-> route melewati `auth` dan `admin.page`
-> tidak melewati `transaction.entry`
-> request mencapai `StoreSupplierInvoiceController`
-> controller memanggil `CreateSupplierInvoiceFlowHandler`
-> flow membuat supplier invoice
-> flow membuat supplier payment sebesar invoice total
-> jika `auto_receive` aktif, flow membuat supplier receipt
-> flow membuat inventory movement `stock_in`
-> inventory projection berubah tanpa capability input transaksi

## Root cause

Route admin mutation untuk supplier invoice ditempatkan di bawah admin-page access saja.

Admin-page access bukan authorization boundary untuk transaksi. Untuk mutation bernilai tinggi seperti supplier invoice, supplier payment, receipt, dan stock movement, route harus melewati `transaction.entry`.

## Dampak

Bypass ini memengaruhi integritas finansial dan inventory.

Actor admin yang seharusnya tidak boleh input transaksi tetap dapat:

- membuat supplier invoice
- membuat payable/supplier payment pending
- membuat receipt bila auto-receive aktif
- membuat stock-in inventory movement
- mengubah inventory projection

## Patch summary

`routes/web/admin_procurement.php` diubah pada route:

`Route::post('/admin/procurement/supplier-invoices', StoreSupplierInvoiceController::class)`

dengan menambahkan:

`->middleware('transaction.entry')`

Patch commit yang dilaporkan:

`dbdb5d8 - Protect admin supplier invoice store route with transaction entry gate`

## Verification

Reported successful checks:

- `php -l routes/web/admin_procurement.php`
- `git diff -- routes/web/admin_procurement.php`

## Verification gap

Belum ada route-list atau feature test yang membuktikan behavior authorization secara runtime.

Future verification:

- `php artisan route:list --path=admin/procurement/supplier-invoices`
- feature test admin role dengan transaction-entry inactive ditolak saat POST supplier invoice
- feature test admin role dengan transaction-entry active tetap bisa membuat supplier invoice
- pastikan route read-only procurement tetap tidak ikut diblokir tanpa alasan

## Relations

Related to #020.

#020 covers admin note mutation routes bypassing the transaction-entry capability gate.

#027 covers admin procurement supplier-invoice creation bypassing the same transaction-entry capability gate.

Related to #016 as part of identity/access capability authorization.

#016 covers unauthenticated admin capability toggle endpoints, while #027 covers a privileged business mutation route missing the capability gate.

Related to #023 only indirectly through procurement proof/storage surfaces, but the root cause is different. #027 is authorization on supplier invoice mutation, not public storage exposure.
