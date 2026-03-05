Blueprint
0) Scope & prinsip inti ✅

Web app dipakai Admin saja (login).

Kasir tidak login web; kasir hanya via Telegram bot untuk cek stok & harga.

Input “laporan kasir” tetap via WA → foto → admin input manual (bot tidak menggantikan WA).

Cash-basis profit:

transaksi Draft (belum lunas) tidak masuk profit

profit masuk di tanggal pelunasan

refund pakai tanggal refund

Stok:

Draft melakukan reserve (mengurangi available, tidak mengurangi on-hand)

reserve tidak boleh minus (kalau kurang → blok simpan)

stok bertambah hanya lewat faktur

stok berkurang lewat Paid dan Stock Adjustment (wajib alasan)

COGS: moving average (avg_cost).

Faktur supplier:

menambah stok + membentuk avg_cost

status lunas/tidak tidak mempengaruhi profit

reminder H-5 + bukti bayar multi-file

pembayaran sekali (no cicilan)

Media report hanya untuk bukti bayar faktur, disimpan di storage/app/private.

1) Domain Modules (bounded context) 🧱

Auth & User

Produk & Inventory

Faktur Supplier & Bukti Bayar

Transaksi Pembeli (Penjualan + Servis)

Biaya Operasional

Gaji

Hutang Karyawan (Pinjam + Pembayaran)

Reporting

Telegram Bot

2) Data Model (tabel inti) 📚

Ini blueprint level—nama tabel/kolom bisa kamu fine-tune saat eksekusi, tapi relasi & konsepnya sudah “fixed”.

2.1 Users & Auth

users

id

username (unique)

password_hash (nullable untuk kasir)

role enum: admin, cashier

is_active bool

Keputusan desain (rekomendasi): kasir tetap ada row di users (role=cashier), tapi tidak punya password dan diblok dari login web via middleware.

2.2 Produk & Inventory

products

id, code unique, name, brand, size, sale_price (int), is_active

product_inventory

product_id PK/FK

on_hand_qty (int)

reserved_qty (int)

avg_cost (int/decimal)

inventory_movements (ledger)

id, product_id

type enum: invoice_in, sale_out, refund_in, adjust_in, adjust_out, reserve, release

qty (+/-)

unit_cost (nullable; wajib untuk movement yang mempengaruhi valuation)

ref_type + ref_id (link ke invoice/transaction/etc)

note, created_at

2.3 Faktur Supplier

supplier_invoices

id, invoice_no unique, supplier_name

delivered_at, due_at

is_paid, paid_at nullable

grand_total (int)

note

supplier_invoice_items

id, supplier_invoice_id, product_id

qty

total_cost (int) ✅ input total per item

unit_cost (int) ✅ sistem = total_cost/qty (dibulatkan rule kamu)

supplier_invoice_media

id, supplier_invoice_id

path_private, original_name, mime, size

uploaded_by (admin), uploaded_at

2.4 Transaksi Pembeli

customer_transactions

id

customer_name (free text)

status enum: draft, paid, canceled, refunded

transacted_at (tanggal dibuat)

paid_at nullable (tanggal pelunasan)

refunded_at nullable

note

customer_transaction_lines

id, customer_transaction_id

kind enum:

product_sale (jualan sparepart/produk)

service_fee (jasa servis)

service_product (servis pakai barang toko: mengurangi stok saat Paid)

outside_cost (biaya belanja luar untuk servis: uang keluar)

product_id nullable (wajib untuk product_sale & service_product)

qty (untuk line yang pakai stok)

amount (int) ✅ nominal (revenue untuk sale/service; expense untuk outside_cost)

cogs_amount (int, nullable; diisi saat Paid untuk line stok)

note

Tidak ada partial di 1 “no transaksi”. Jika sebagian dibayar, dibuat “no” terpisah (sudah kamu kunci).

2.5 Operasional, Gaji, Hutang Karyawan

operational_expenses: name, spent_at, amount, note

employees: name (minimal)

salaries: employee_id, paid_at, amount, note

employee_loans: employee_id, loaned_at, amount, note (uang keluar)

employee_loan_payments: employee_loan_id, paid_at, amount, note (uang masuk)

2.6 Telegram

telegram_link_tokens

id, token_hash, role (admin/cashier), is_used, used_at

telegram_links

id, user_id, telegram_chat_id, linked_at

3) Rules Engine (stok + profit) ⚙️
3.1 Inventory quantities

available = on_hand_qty - reserved_qty

Saat transaksi Draft:

untuk setiap line yang “pakai stok” (product_sale, service_product):

validasi available >= qty

tambah reserved_qty dan buat movement reserve

Saat Draft diubah/dihapus:

release reserve yang berubah → movement release

3.2 Finalisasi Paid

Saat status Draft → Paid:

set paid_at (tanggal pelunasan)

untuk tiap line stok:

reserved_qty -= qty

on_hand_qty -= qty

cogs_amount = qty * avg_cost (avg_cost saat itu)

movement sale_out dengan unit_cost = avg_cost

3.3 Refund

Saat Paid → Refunded:

refunded_at dipakai untuk cash-out refund

untuk tiap line stok yang di-refund:

on_hand_qty += qty

movement refund_in dengan unit_cost = unit_cost saat sale (agar valuation konsisten)

catat cash-out refund sebagai bagian report (bisa diambil dari total revenue yang direfund atau field refund_amount bila kamu ingin eksplisit)

4) Reporting definition (harian/bulanan) 📊

Per tanggal (daily) atau rentang bulan:

Cash In

transaksi Paid pada tanggal pelunasan: sum revenue lines (product_sale, service_fee, service_product)

pembayaran hutang karyawan (employee_loan_payments)

Cash Out

refund pada tanggal refund (uang keluar)

operasional

gaji

pinjaman karyawan (employee_loans)

biaya luar servis (outside_cost)

COGS

sum cogs_amount dari transaksi Paid (line stok)

Profit

profit = cash_in - cash_out - cogs

Faktur supplier tidak masuk profit, tapi mempengaruhi avg_cost (COGS).

5) Laravel Architecture (controller 1 fungsi) 🧩
5.1 Pola folder (disarankan)

app/Http/Controllers/Admin/... → web admin (single-action)

app/Http/Controllers/Telegram/WebhookController.php → 1 endpoint webhook

app/Application/UseCases/... → business flow per aksi

app/Domain/... → entity/value object + service domain (inventory, reporting)

app/Infrastructure/... → repository impl, telegram client, storage adapter

5.2 Single-action controllers (contoh)

Produk

Admin/Products/CreateController (form)

Admin/Products/StoreController

Admin/Products/IndexController

Admin/Products/UpdatePriceController (khusus harga jual)

Faktur

Admin/Invoices/CreateController

Admin/Invoices/StoreController

Admin/Invoices/MarkPaidController

Admin/Invoices/UploadProofController

Admin/Invoices/DownloadProofController

Transaksi

Admin/Transactions/CreateDraftController

Admin/Transactions/StoreDraftController

Admin/Transactions/MarkPaidController

Admin/Transactions/RefundController

Admin/Transactions/CancelController

Reporting

Admin/Reports/DailyProfitController

Admin/Reports/MonthlyProfitController

Admin/Reports/StockController

Admin/Reports/InvoiceDueSoonController

6) Views + JS Native (Mazer) 🖥️

resources/views/layouts/mazer.blade.php

resources/views/admin/products/...

resources/views/admin/invoices/...

resources/views/admin/transactions/...

resources/views/admin/reports/...

JS native per halaman:

resources/js/pages/products/index.js

resources/js/pages/invoices/create.js

dst…

Form submit tetap server-rendered; JS hanya enhancement (search, filter, modal, dynamic rows).

7) Auth constraints 🔐

Tidak ada register / forgot password.

Web login hanya admin:

route login + session

middleware admin.only

Kasir hanya akses Telegram.
