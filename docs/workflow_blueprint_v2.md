Blueprint target project

Target akhirnya bukan sekadar “fitur jalan”, tetapi fondasi operasional yang stabil untuk 2 fase berikutnya:

fase operasional web admin selesai

stok akurat

barang rapi

suplai masuk konsisten

transaksi mengikuti kebiasaan owner

laporan bisa dipercaya

fase ekspansi jadi mudah

bot Telegram tinggal memakai service/use case yang sudah rapi

laporan PDF tinggal memakai presenter/query/report builder yang sudah stabil

tidak perlu bongkar domain inti lagi

Kontrak domain final yang dituju
1. Barang

products = master barang
Tujuan:

satu sumber nama/kode/harga jual/status aktif

dipakai oleh stok, suplai, transaksi, laporan

2. Stok

product_inventory + inventory_movements = source of truth stok
Tujuan:

semua perubahan stok tercatat lewat movement

tidak ada mutasi stok “diam-diam”

3. Suplai

supplier_invoices + items = pintu masuk stok dari supplier
Tujuan:

on_hand dan avg_cost dibentuk dari suplai yang valid

jadi dasar COGS saat transaksi paid

4. Transaksi pelanggan

UI utama = Nota Pelanggan
Backend:

customer_orders = nota pelanggan

customer_transactions = kasus

customer_transaction_lines = rincian

Tujuan:

kebiasaan owner tetap terasa seperti 1 nota

lifecycle tetap akurat per kasus

5. Laporan

Report builder membaca dari transaksi/stok/suplai yang sudah final
Tujuan:

laporan tidak punya logika liar sendiri

hanya membaca domain yang sudah benar

Masalah inti yang harus dibereskan dulu

Ini saya susun dari audit kode yang sudah Anda kirim.

A. Transaksi masih belum nota-centric

Fakta:

route aksi inti masih di /admin/transactions

create/edit/show/refund masih transaksi-centric

customer_orders.show belum jadi workspace utama

Efek:

owner tetap merasa 3 layer

B. Payment model belum mendukung hutang bertahap

Fakta:

MarkPaidRequest hanya punya paid_at

MarkPaidCustomerTransactionUseCase langsung ubah draft -> paid

belum ada tabel payment / partial payment / outstanding

Efek:

kebutuhan “bayar hari ini, besok lanjut” belum punya jejak data yang kuat

C. Refund masih terlalu kaku

Fakta:

refund hanya boleh sekali per child

refund_amount terpisah dari item qty dan belum tervalidasi sinkron penuh

Efek:

refund parsial berulang belum aman

D. Customer order belum punya konsep nota aktif eksplisit

Fakta:

customer_orders hanya punya customer_name, note, timestamps

Efek:

aturan “1 pelanggan = 1 nota aktif” belum bisa ditegakkan di domain

E. UI status belum jujur

Fakta:

draft => Hutang

refunded => Batal

Efek:

makna domain dan makna tampilan masih bentrok

Workflow perubahan end-to-end

Saya pecah ke fase agar nanti bisa kita kerjakan satu per satu tanpa keluar jalur.

Fase 0 — Bekukan kontrak domain dan istilah UI
Tujuan

Menyepakati bahasa final dan rule inti agar fase berikutnya tidak bolak-balik.

Fakta dasar

backend parent-child sudah ada

child lebih cocok disebut “kasus”

line lebih cocok disebut “rincian”

owner berpikir dalam 1 nota, bukan banyak transaksi terpisah

Output

keputusan istilah UI final

keputusan status UI final

keputusan rule delete/cancel/refund final

keputusan apakah hutang bertahap didukung penuh sekarang atau ditunda

DoD

ada dokumen keputusan singkat

semua istilah final disetujui

tidak ada ambiguitas lagi soal:

Nota

Kasus

Rincian

Draft / Belum lunas / Lunas / Batal / Refund

Fase 1 — Refactor UI transaksi menjadi nota-centric
Tujuan

Membuat admin merasa sedang mengisi 1 nota pelanggan.

Fakta dasar

customer_orders.show sudah ada

create/edit form kasus sudah ada

JS native line editor sudah ada

masalah utama ada di penamaan, redirect, dan pusat workflow

Lingkup

customer_orders.show jadi workspace utama

istilah UI diganti:

Transaksi -> Kasus

Line -> Rincian

redirect aksi kembali ke detail nota

transactions.show turun fungsi menjadi halaman internal saja atau support page

Output

halaman nota pelanggan menjadi pusat kerja

tambah/edit kasus tetap bisa reuse form lama

user tidak perlu mental model 3 layer

DoD

dari detail Nota Pelanggan, admin bisa:

tambah kasus

edit kasus draft

lunaskan kasus

batalkan kasus

refund kasus

setelah tiap aksi, user kembali ke detail nota yang sama

tidak ada flow utama yang memaksa user kerja dari index transaksi

istilah “transaksi” tidak dominan di UI owner

Fase 2 — Tegakkan rule “1 nota aktif per pelanggan”
Tujuan

Menjadikan aturan ini rule domain, bukan kebiasaan manual.

Fakta dasar

saat ini create kasus tanpa customer_order_id selalu membuat parent baru

belum ada field aktif/tutup di customer order

Lingkup

tambah field status nota aktif/tutup, atau field ekuivalen yang eksplisit

ubah create use case agar:

cari nota aktif customer

pakai nota aktif bila ada

buat nota baru hanya bila memang belum ada nota aktif / user memilih tutup nota lama dan buka baru

sinkronkan source of truth nama customer

Output

satu customer tidak punya banyak nota aktif yang ambigu

create kasus baru otomatis masuk ke nota aktif yang benar

DoD

ada representasi data yang eksplisit untuk nota aktif/tutup

create kasus baru tidak membuat parent duplikat liar

nama customer punya source of truth yang final

test manual membuktikan:

customer yang sama menambah kasus tetap masuk ke nota aktif

nota baru tidak tercipta tanpa alasan domain yang sah

Fase 3 — Finalkan lifecycle pembayaran
Tujuan

Menentukan dengan tegas apakah sistem mendukung hutang bertahap atau hanya draft/lunas.

Fakta dasar

kebutuhan bisnis Anda menyebut bayar hari ini, besok lanjut

kode sekarang baru mendukung full-paid sekali

Keputusan yang harus diambil di fase ini

Ada 2 jalur.

Jalur A — sederhana

belum bikin tabel payment

status hanya:

draft

paid

canceled

refunded

“hutang” di UI diturunkan menjadi “belum lunas”, bukan hutang bertahap yang akuntabel

Jalur B — final operasional

tambah tabel pembayaran transaksi

dukung partial payment

punya outstanding amount

status UI bisa jujur:

Belum Lunas

Sebagian

Lunas

Batal

Refund

Rekomendasi saya

Untuk target Anda “agar nanti fase Telegram bot dan PDF gampang”, jalur B lebih sehat.
Karena bot dan PDF akan sulit bila pembayaran belum punya struktur eksplisit.

DoD jalur B

ada tabel payment atau struktur ekuivalen

pembayaran bisa lebih dari sekali

outstanding bisa dihitung dari data, bukan asumsi

status UI dibentuk dari angka pembayaran nyata

laporan harian/bulanan membaca pembayaran secara benar

Fase 4 — Finalkan refund agar jujur dan stabil
Tujuan

Membuat refund konsisten terhadap stok, nominal, dan histori.

Fakta dasar

refund qty line sudah ada

refund amount header sudah ada

saat ini hanya boleh sekali per child

Lingkup

putuskan apakah refund boleh:

sekali saja

atau berkali-kali sebagai event

sinkronkan nominal refund dengan item refund

pisahkan makna canceled vs refunded di UI dan laporan

Rekomendasi

Kalau kebutuhan nyata owner memang bisa refund bertahap, model terbaik adalah:

refund event terpisah

item refund terpisah
bukan hanya satu refund_amount + refunded_at di header child

DoD

refund tidak bisa membuat stok/uang inkonsisten

refund parsial terbaca jelas di UI

laporan bisa membedakan:

penjualan batal

penjualan yang sempat lunas lalu refund

Fase 5 — Rapikan master barang dan aturan harga
Tujuan

Menstabilkan kontrak barang sebelum bot dan PDF memakainya.

Fakta dasar

transaksi stok memakai Product.sale_price

request sekarang memaksa minimal amount line stok = qty x sale_price

Lingkup

putuskan apakah harga jual minimum ini tetap keras

atau boleh diskon/manual override dengan alasan tertentu

pastikan field barang yang dibutuhkan laporan dan transaksi sudah cukup

DoD

aturan harga jual tertulis jelas

transaksi mengikuti aturan itu secara konsisten

laporan revenue tidak ambigu karena aturan diskon/harga override tidak jelas

Fase 6 — Finalkan suplai dan biaya perolehan
Tujuan

Memastikan suplai menjadi sumber stok masuk dan avg_cost yang benar.

Fakta dasar

modul supplier invoice sudah ada

stok dan COGS transaksi bergantung pada inventory

Lingkup

audit flow create supplier invoice -> stock in -> avg_cost

pastikan invoice supplier tidak bisa merusak stok

pastikan attachment/media proof bekerja sesuai kebutuhan

DoD

stok masuk hanya dari source domain yang sah

avg_cost berubah konsisten setelah suplai masuk

data supplier invoice cukup untuk audit manual

Fase 7 — Kunci laporan dari domain yang sudah final
Tujuan

Membuat laporan harian/bulanan/stok berdiri di atas data yang sudah bersih.

Fakta dasar

report use case sudah ada

tetapi validitas laporan sangat bergantung pada transaksi, refund, payment, dan suplai yang final

Lingkup

audit rumus report use case

cocokkan terhadap domain final fase 3–6

siapkan bentuk output yang mudah dipakai PDF nanti

DoD

laporan profit harian dan bulanan cocok dengan hitung manual sample

laporan stok cocok dengan inventory movement

laporan hutang/piutang hanya muncul bila payment model memang final

semua report builder tidak menyimpan logika liar di blade

Fase 8 — Rapikan service boundary untuk Telegram bot dan PDF
Tujuan

Membuat integrasi fase berikutnya jadi ringan.

Fakta dasar

Anda ingin nanti bot Telegram dan PDF gampang

itu berarti query dan action harus rapi lebih dulu

Lingkup

pastikan semua aksi inti ada di use case/service, bukan di controller/blade

buat presenter/query object untuk:

ringkasan nota

ringkasan kasus

laporan harian

laporan bulanan

stok

siapkan kontrak data untuk output PDF dan pesan Telegram

DoD

controller tipis

view tidak menghitung domain berat

data yang dipakai Telegram/PDF bisa diambil dari service/query yang sama

tidak perlu duplikasi aturan bisnis di kanal baru

Prioritas urutan kerja yang saya sarankan

Urutan terbaik dari kondisi kode Anda sekarang:

Fase 0 — keputusan domain dan istilah

Fase 1 — UI transaksi jadi nota-centric

Fase 2 — rule 1 nota aktif

Fase 3 — payment lifecycle

Fase 4 — refund lifecycle

Fase 5 — barang/harga

Fase 6 — suplai/avg cost

Fase 7 — laporan

Fase 8 — hardening untuk Telegram bot + PDF

Urutan ini dipilih karena:

UI tidak boleh difinalkan sebelum istilah dan rule domain jelas

laporan tidak boleh dikunci sebelum payment/refund final

bot dan PDF paling murah dikerjakan setelah query/use case stabil

Definition of Done global project

Ini DoD lintas fase. Setiap fase harus lolos ini sesuai konteksnya.

DoD global

perubahan mengikuti domain final yang disepakati

tidak menambah layer UI yang membingungkan owner

controller tetap tipis, logika inti ada di use case/service

tidak ada perubahan yang merusak akurasi stok

route utama owner konsisten ke workspace yang benar

istilah UI konsisten di semua view

skenario manual penting bisa diuji end-to-end

tidak ada status/label UI yang membohongi keadaan data

laporan membaca source of truth, bukan workaround di blade

DoD teknis minimum per perubahan

migration aman

model relation benar

request validation sesuai domain final

controller redirect benar

use case tidak melanggar invariant stok/uang

blade tidak mengandung logika domain berat

JS native tetap progressive dan tidak wajib untuk submit dasar

route:list tetap rapi

sanity test manual dicatat

Workflow eksekusi per step

Karena Anda ingin kita kerjakan 1 per 1, maka pola kerjanya saya sarankan begini:

Untuk setiap fase

tetapkan fakta dan target fase

tentukan file yang disentuh

ubah domain/route/request/use case/view seperlunya

cek alur manual

update kesimpulan fase

tampilkan progres total workflow

Dengan total 9 fase di atas, progres kasar bisa dibaca:

selesai Fase 0 = 11%

selesai Fase 1 = 22%

selesai Fase 2 = 33%

selesai Fase 3 = 44%

selesai Fase 4 = 56%

selesai Fase 5 = 67%

selesai Fase 6 = 78%

selesai Fase 7 = 89%

selesai Fase 8 = 100%

Langkah pertama yang paling tepat sekarang

Karena kita belum boleh lompat implementasi tanpa kontrak final, langkah pertama adalah Fase 0.

Data/fakta yang sudah cukup untuk Fase 0

Sudah ada:

tree project

route

model

migration

controller

use case

request

views

Jadi untuk Fase 0, kita tidak butuh data tambahan dulu.

Output Fase 0 yang akan saya susun berikutnya

Kalau Anda setuju, saya lanjut di pesan berikutnya dengan:

Dokumen keputusan Fase 0, berisi:

istilah UI final

status UI final

rule delete/cancel/refund final

keputusan awal jalur payment:

sederhana dulu

atau langsung partial payment siap operasional

Itu akan jadi fondasi untuk Fase 1 dan seterusnya.

Progres saat ini: 0% dari workflow perubahan, karena kita baru menyusun rencana dan belum menutup satu fase pun.
