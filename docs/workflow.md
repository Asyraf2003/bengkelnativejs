# Workflow + DoD (1 dokumen) — Laravel Akuntansi + Stok + Faktur + Telegram Bot

Dokumen ini adalah **rencana kerja berurutan** sekaligus **Definition of Done (DoD)** per tahap.
Aturan utama: **kerja 1–1**, dan **tidak keluar jalur** dari blueprint/invariant yang sudah disepakati.

---

## 0) Invariant (tidak boleh berubah tanpa revisi blueprint)

### 0.1 Role & akses
- Web app: **hanya Admin** yang bisa login.
- Kasir: **tidak login web**, hanya pakai Telegram bot untuk:
  - cek stok (available)
  - cek harga jual
  - search produk
- Tidak ada: register, forgot password.

### 0.2 Profit basis (cash-basis sesuai aturan)
- Transaksi **Draft (belum lunas)**:
  - **tidak masuk profit**
  - stok **tidak berkurang on-hand**
  - stok **mengurangi available** lewat reserve
- Transaksi **Paid**:
  - masuk profit pada **tanggal pelunasan (paid_at)**
- Refund:
  - **uang keluar** pada **tanggal refund (refunded_at)**
  - barang **balik ke stok**

### 0.3 Stok & inventory
- Stok bertambah **hanya lewat Faktur Supplier**.
- Stok berkurang:
  - saat transaksi Paid (line yang pakai stok)
  - Stock Adjustment (wajib alasan)
- Draft reserve:
  - reserve **tidak boleh membuat available minus** → sistem **blok** simpan
- Definisi:
  - on_hand = stok fisik
  - reserved = stok terpakai Draft
  - available = on_hand - reserved

### 0.4 COGS & cost
- Metode COGS: **moving average (avg_cost)**.
- Faktur supplier:
  - item input: **total_cost per item**, sistem hitung unit_cost = total_cost / qty
  - mempengaruhi avg_cost dan menambah stok
  - status lunas/tidak lunas faktur **tidak mempengaruhi profit**
- Tidak ada retur supplier.

### 0.5 Transaksi pembeli
- Detail transaksi **wajib line items**.
- Status transaksi: draft, paid, canceled, refunded.
- Partial payment:
  - **tidak ada partial per 1 no transaksi**
  - jika sebagian dibayar, dibuat **no transaksi terpisah**

### 0.6 Servis
- Servis bisa:
  - hanya jasa (tanpa stok)
  - jasa + pakai barang toko (stok berkurang saat Paid)
  - jasa + barang luar (outside_cost sebagai pengeluaran)
- Outside_cost: hanya **nominal cost**.

### 0.7 Media (bukti bayar faktur)
- Media report hanya untuk **bukti bayar faktur**.
- Simpan di `storage/app/private` + metadata DB.
- Multi-file per faktur.

### 0.8 Telegram
- Linking via **token sekali pakai**.
- Token terpisah admin & kasir (role-based menu).
- Reminder H-5: kirim ke admin jam **09:00 Asia/Makassar**.

---

## 1) Struktur kerja (gating)

Setiap fase punya:
- **Input wajib**: data/snapshot yang harus ada sebelum mulai.
- **Langkah**: tugas konkret.
- **DoD**: checklist lulus fase.
- **Verifikasi manual**: langkah untuk cek matematika/invariant.

Aturan kerja:
- Setelah selesai 1 fase → stop → review hasil → lanjut fase berikutnya.

---

## 2) Fase 0 — Skeleton + Auth Admin (tanpa register/forgot)

### Input wajib sebelum mulai
- Versi PHP & Laravel (output `php -v` dan `php artisan --version`)
- DB yang dipakai (`DB_CONNECTION`)
- Template Mazer sudah disiapkan (file asset/HTML) atau belum

### Langkah
1) Buat project Laravel (jika belum ada) + konfigurasi `.env` DB.
2) Buat sistem login Admin:
   - halaman login (Mazer)
   - session auth
   - middleware `admin.only`
3) Model `users`:
   - kolom `role` enum (admin|cashier)
   - password nullable (cashier tanpa password)
4) Kasir diblok dari login web:
   - validasi di login handler: hanya role=admin yang bisa login
5) Seeder:
   - buat 1 admin default
   - buat 1 cashier default (tanpa password)

### DoD (lulus fase 0)
- [ ] Admin bisa login web dan logout.
- [ ] Kasir **tidak bisa login web** (ditolak).
- [ ] Tidak ada route register/forgot password.
- [ ] Middleware admin-only aktif pada semua route admin.
- [ ] Seeder menghasilkan admin+cashier sesuai rule.

### Verifikasi manual
- Login admin → akses dashboard.
- Coba login pakai user kasir → gagal.

---

## 3) Fase 1 — Produk + Inventory core (on_hand, reserved, available)

### Input wajib
- Format kode produk sudah disepakati: **unik per varian**
- Field produk minimum: code, name, brand, size, sale_price

### Langkah
1) Tabel & model:
   - products
   - product_inventory (on_hand_qty, reserved_qty, avg_cost)
   - inventory_movements (ledger)
2) CRUD Produk:
   - list + search
   - create/edit
   - update harga jual (opsional single-action terpisah)
3) Stock Adjustment:
   - hanya admin
   - nambah/kurang dengan alasan wajib
   - movement dicatat
4) UI:
   - tampil on_hand, reserved, available

### DoD (lulus fase 1)
- [ ] Produk bisa dibuat/diubah/dinonaktifkan.
- [ ] Inventory row otomatis ada untuk setiap produk.
- [ ] Adjustment tidak bisa disimpan tanpa alasan.
- [ ] Ledger movement tercatat untuk adjustment.
- [ ] available = on_hand - reserved selalu konsisten.

### Verifikasi manual
- Buat 1 produk:
  - stok awal on_hand=0 reserved=0 available=0
- Adjustment +10:
  - on_hand=10 reserved=0 available=10
- Adjustment -3:
  - on_hand=7 reserved=0 available=7
- Cek ledger movement sesuai operasi.

---

## 4) Fase 2 — Faktur Supplier (nambah stok + avg_cost) + Media bukti bayar

### Input wajib
- Rule due date:
  - due_at = tanggal sama bulan depan
  - jika tanggal tidak ada → akhir bulan

### Langkah
1) Tabel & model:
   - supplier_invoices
   - supplier_invoice_items
   - supplier_invoice_media
2) Form faktur:
   - invoice_no unique
   - supplier_name, delivered_at, due_at auto-calc (editable bila perlu)
   - item: pilih product + qty + total_cost
   - sistem: unit_cost = floor/round sesuai rule pembulatan yang dipilih (WAJIB ditetapkan saat implementasi)
   - grand_total = sum total_cost
3) Posting faktur:
   - untuk tiap item:
     - update on_hand += qty
     - update avg_cost (moving average)
     - ledger movement invoice_in
4) Media:
   - upload multi-file bukti bayar
   - download hanya admin
   - storage `storage/app/private`

### DoD (lulus fase 2)
- [ ] Faktur menambah stok dan ledger tercatat.
- [ ] avg_cost berubah sesuai moving average.
- [ ] Bukti bayar bisa upload >1 file per faktur.
- [ ] File tersimpan private dan tidak bisa diakses publik.
- [ ] Status paid/unpaid ada, tapi tidak mempengaruhi profit.

### Verifikasi manual (avg_cost)
Gunakan contoh:
- Produk X avg_cost awal 0, on_hand 0
- Faktur 1: qty 10, total_cost 100.000 → unit_cost 10.000
  - on_hand 10, avg_cost 10.000
- Faktur 2: qty 10, total_cost 200.000 → unit_cost 20.000
  - avg_cost baru = (10*10.000 + 10*20.000) / (10+10) = 15.000
  - on_hand 20, avg_cost 15.000

Catat hasil manual lalu bandingkan output sistem.

---

## 5) Fase 3 — Transaksi pembeli (Draft reserve → Paid → Refund)

### Input wajib
- Jenis line items:
  - product_sale (pakai stok)
  - service_fee (tanpa stok)
  - service_product (pakai stok)
  - outside_cost (pengeluaran)
- Rule: tidak ada partial per 1 transaksi

### Langkah
1) Tabel & model:
   - customer_transactions
   - customer_transaction_lines
2) Create Draft:
   - input customer_name, transacted_at (default hari ini)
   - input line items (multi)
   - validasi reserve:
     - untuk line stok (product_sale, service_product):
       - available >= qty (jika tidak, blok simpan)
     - reserved += qty
     - ledger reserve
3) Mark Paid:
   - set paid_at (tanggal pelunasan)
   - untuk line stok:
     - reserved -= qty
     - on_hand -= qty
     - cogs_amount = qty * avg_cost (pada saat paid)
     - ledger sale_out
4) Cancel:
   - release reserve (ledger release)
   - status canceled
5) Refund:
   - set refunded_at
   - uang keluar (refund) dicatat sebagai komponen report
   - stok balik:
     - on_hand += qty untuk line stok yang direfund
     - ledger refund_in dengan unit_cost konsisten (disarankan simpan unit_cost sale di line untuk dipakai saat refund)

### DoD (lulus fase 3)
- [ ] Draft tidak masuk profit dan tidak mengurangi on_hand.
- [ ] Draft membuat reserved dan available turun.
- [ ] Sistem menolak draft jika reserve membuat available minus.
- [ ] Paid mengurangi on_hand dan release reserved.
- [ ] Paid mengisi cogs_amount dan ledger sale_out tercatat.
- [ ] Cancel release reserved tanpa mengubah on_hand.
- [ ] Refund mengembalikan stok dan tercatat sebagai uang keluar pada tanggal refund.

### Verifikasi manual (stok & reserve)
Kasus:
- Produk A on_hand=5 reserved=0 available=5
- Draft: qty 3 → reserved=3 available=2 on_hand tetap 5
- Draft lain: qty 3 → harus gagal (available hanya 2)
- Paid draft pertama:
  - reserved=0
  - on_hand=2
  - available=2

---

## 6) Fase 4 — Operasional, Gaji, Hutang Karyawan

### Input wajib
- Semua punya `note/keterangan` (wajib tersedia)
- Hutang karyawan: pembayaran bisa berkali-kali

### Langkah
1) Operasional:
   - create/list/filter per tanggal
2) Gaji:
   - employees master
   - salaries transaksi per tanggal
3) Hutang:
   - employee_loans (uang keluar)
   - employee_loan_payments (uang masuk) multi payment

### DoD (lulus fase 4)
- [ ] Semua modul bisa input tanggal manual (default hari ini).
- [ ] Semua ada keterangan.
- [ ] Hutang bisa dicicil (multi payment).
- [ ] Tidak ada efek stok dari modul ini.

### Verifikasi manual
- Input pinjaman 100.000 hari ini → harus muncul di cash_out.
- Input pembayaran 30.000 besok → harus muncul di cash_in besok.

---

## 7) Fase 5 — Reporting (profit daily & monthly, stok, due invoice H-5)

### Input wajib
- Formula profit final (sesuai invariant):

  cash_in:
  - transaksi Paid pada paid_at: sum revenue (product_sale, service_fee, service_product)
  - employee_loan_payments (uang masuk)

  cash_out:
  - refund pada refunded_at
  - operational_expenses
  - salaries
  - employee_loans (uang keluar)
  - outside_cost

  cogs:
  - sum cogs_amount pada transaksi Paid

  profit:
  - cash_in - cash_out - cogs

- Faktur supplier tidak mempengaruhi profit (pembayaran faktur diabaikan untuk profit)

### Langkah
1) Report Profit Harian:
   - filter tanggal X
   - hitung komponen cash_in, cash_out, cogs
2) Report Profit Bulanan:
   - filter month/year
   - agregasi per hari + total bulanan
3) Report Stok:
   - list produk dengan on_hand, reserved, available, avg_cost
4) Report Faktur H-5:
   - tampilkan faktur due_at dalam 5 hari ke depan (relative dari hari ini)
   - status paid/unpaid

### DoD (lulus fase 5)
- [ ] Profit harian sesuai formula cash-basis (paid_at/refunded_at).
- [ ] Profit bulanan = agregasi harian yang konsisten.
- [ ] Faktur supplier tidak mempengaruhi profit.
- [ ] Laporan stok tampil lengkap.
- [ ] Laporan faktur H-5 benar.

### Verifikasi manual (matematika profit)
Protokol:
1) Pilih 1 tanggal T.
2) Ambil daftar transaksi Paid dengan paid_at=T:
   - jumlahkan revenue per line (kecuali outside_cost)
   - jumlahkan cogs_amount
3) Ambil semua cash_out tanggal T:
   - operasional, gaji, pinjaman karyawan, outside_cost, refund (refunded_at=T)
4) Ambil cash_in non-transaksi tanggal T:
   - pembayaran hutang karyawan
5) Hitung manual:
   - profit_manual = cash_in - cash_out - cogs
6) Bandingkan dengan report sistem.
7) Ulangi untuk 3 skenario:
   - hari profit positif
   - hari minus (ada gaji/pinjaman besar)
   - hari refund

---

## 8) Fase 6 — Telegram Bot (role-based menu + reminder)

### Input wajib
- Token linking: dibuat admin di web, sekali pakai
- Jam reminder: 09:00 Asia/Makassar
- Reminder hanya ke admin

### Langkah
1) Webhook endpoint:
   - verifikasi secret/token bot
2) Linking flow:
   - user klik start → bot minta token
   - token valid → link ke telegram_chat_id → mark token used
3) Menu Kasir:
   - search produk (kode/nama)
   - tampil stok available + harga jual
4) Menu Admin:
   - cek faktur paid/unpaid
   - due soon H-5
   - upload bukti bayar faktur (via telegram file)
   - download/lihat list bukti bayar (kirim file)
   - profit harian & bulanan
5) Scheduler reminder:
   - daily 09:00 WITA → ambil faktur due dalam 5 hari dan unpaid → kirim ke admin chat_id

### DoD (lulus fase 6)
- [ ] Linking token sekali pakai bekerja.
- [ ] Kasir hanya dapat menu terbatas.
- [ ] Admin dapat menu lengkap.
- [ ] Upload bukti bayar via telegram tersimpan ke private storage + bisa diambil kembali.
- [ ] Reminder H-5 otomatis jam 09:00 WITA hanya ke admin.

### Verifikasi manual
- Link kasir → pastikan hanya bisa query produk.
- Link admin → pastikan bisa akses due invoice + profit.
- Set faktur due_at 5 hari lagi → tunggu/simulasikan scheduler → reminder terkirim.
  (Untuk simulasi cepat: jalankan scheduler command manual di dev.)

---

## 9) Seeder simulasi (wajib untuk test sebelum launch)

Tujuan:
- Menghasilkan dataset realistis untuk:
  - uji UI
  - uji performa query report
  - verifikasi matematika profit & stok

### 9.1 Prinsip seeding (wajib)
- Deterministik: seed harus bisa diulang dengan hasil sama (gunakan seed number).
- Tidak melanggar invariant:
  - tidak ada reserve minus
  - stok bertambah hanya dari faktur
  - transaksi draft tidak mengurangi on_hand
  - paid mengurangi stok dan set cogs
  - refund mengembalikan stok

### 9.2 Dataset level
- `SMALL`: 20 produk, 5 faktur, 30 transaksi, 10 operasional, 4 gaji, 6 pinjaman + 10 pembayaran
- `MEDIUM`: 200 produk, 30 faktur, 300 transaksi, 60 operasional, 20 gaji, 20 pinjaman + 60 pembayaran
- `LARGE`: 2000 produk, 200 faktur, 5000 transaksi, 500 operasional, 200 gaji, 200 pinjaman + 800 pembayaran

### 9.3 Seeder scenario generator (aturan data)
1) Produk:
   - code unik per varian
   - sale_price random wajar
2) Faktur awal:
   - minimal 1 faktur awal untuk stok & avg_cost awal
   - setiap item qty > 0, total_cost > 0
3) Transaksi:
   - generate draft dan paid terpisah (paid_at bisa berbeda hari)
   - untuk line stok: pastikan available cukup sebelum reserve
   - outside_cost hanya numeric
4) Refund:
   - sebagian transaksi paid ditandai refunded (dengan refunded_at)
   - stok balik sesuai qty line stok
5) Operasional/gaji/hutang:
   - tanggal random dalam rentang (misal 60–180 hari)
   - note selalu terisi

### 9.4 DoD seeder
- [ ] Ada perintah tunggal untuk generate dataset (SMALL/MEDIUM/LARGE).
- [ ] Seeder dapat diulang dengan seed number sama → hasil sama.
- [ ] Setelah seeding, invariants tetap valid:
  - available tidak negatif
  - on_hand tidak negatif
  - reserved tidak negatif
- [ ] Report harian/bulanan bisa dihitung dan dibanding manual sample.

### 9.5 Protokol verifikasi manual pakai data seeder
- Pilih 1 hari T dari dataset:
  - list transaksi paid_at=T + cogs
  - list refund refunded_at=T
  - list operasional/gaji/pinjaman/payments pada T
  - hitung manual profit T
  - cocokkan dengan report sistem
- Pilih 1 produk P:
  - telusuri inventory_movements untuk P
  - pastikan on_hand/reserved/available cocok dengan akumulasi movement

---

## 10) Quality gate minimum (setiap fase)
- [ ] Route admin terproteksi middleware.
- [ ] Validasi form request ada untuk semua create/update.
- [ ] Semua operasi stok menghasilkan inventory_movements.
- [ ] Semua query report punya filter tanggal yang jelas.
- [ ] Semua nominal uang disimpan integer (rupiah).
- [ ] Tidak ada akses publik ke file private.

---

## 11) Catatan implementasi (agar controller tidak gendut)
- Gunakan pola:
  - Controller (single-action) → UseCase → Domain service / Repository
- Controller hanya:
  - authorize
  - validate
  - panggil usecase
  - return response/view

---

## 12) Stop point setelah dokumen ini
Langkah berikutnya (eksekusi fase 0) hanya dimulai setelah tersedia snapshot:
- versi PHP & Laravel
- DB connection
- status project (baru/lanjutan)
- aset Mazer tersedia atau belum
- seed mode yang dipakai dulu: SMALL/MEDIUM/LARGE + seed number
