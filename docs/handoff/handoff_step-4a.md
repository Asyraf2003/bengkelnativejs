# Step 4a — Create/Update Product Master

## Metadata

- **Tanggal:** 2026-03-11
- **Nama slice / topik:** Step 4a — Create/Update Product Master
- **Workflow step:** Step 4 — Product Catalog
- **Status:** SELESAI untuk sub-slice Step 4a
- **Progres:** 100% untuk Step 4a, 60% untuk Step 4 induk

## Target Halaman Kerja

Menutup sub-slice awal Step 4 Product Catalog agar product master resmi hidup sebagai source of truth awal untuk create/update data barang, dengan validasi minimum `harga_jual` dan rule duplicate minimum yang sudah terkunci.

## Referensi yang Dipakai [REF]

- Blueprint:
  - potongan blueprint Product Catalog
- Workflow:
  - potongan workflow Step 4 — Product Catalog
- DoD:
  - tidak dibawa / tidak dipakai pada halaman ini
- ADR:
  - ADR-0012 — Product Master Must Exist Before Supplier Receipt
- Handoff sebelumnya:
  - CATATAN HANDOFF — KASIR BENGKEL — PENUTUP STEP 3

## Snapshot Repo / Output Command yang Dipakai

- `tree -L4 app/Core app/Ports app/Adapters database/migrations routes`
- `cat app/Providers/HexagonalServiceProvider.php`
- `cat routes/web.php`
- `cat app/Core/Shared/ValueObjects/Money.php`
- `cat app/Adapters/In/Http/Controllers/IdentityAccess/EnableAdminTransactionCapabilityController.php`
- `cat app/Adapters/Out/IdentityAccess/DatabaseAdminTransactionCapabilityStateAdapter.php`
- `tree -L4 app/Application`
- `tree -L4 app/Adapters/In/Http/Requests`
- `cat app/Application/Shared/DTO/Result.php`
- `cat app/Adapters/In/Http/Requests/IdentityAccess/EnableAdminTransactionCapabilityRequest.php`
- `cat app/Adapters/In/Http/Presenters/JsonPresenter.php`
- `cat database/migrations/2026_03_10_000100_create_actor_accesses_table.php`
- `cat app/Application/IdentityAccess/UseCases/EnableAdminTransactionCapabilityHandler.php`
- `cat app/Ports/Out/UuidPort.php`
- `cat tests/Feature/IdentityAccess/EnableAdminTransactionCapabilityFeatureTest.php`
- `cat tests/Feature/IdentityAccess/DisableAdminTransactionCapabilityFeatureTest.php`

### Verifikasi syntax/file

- `php -l ...`
- `tree -L4 ...`

### Verifikasi route

- `php artisan route:list | grep product-catalog`

### Verifikasi test

- `php artisan test tests/Feature/ProductCatalog/CreateProductFeatureTest.php`
- `php artisan test tests/Feature/ProductCatalog/UpdateProductFeatureTest.php`

## Fakta Terkunci [FACT]

- Sebelum halaman ini dibuka, repo belum memiliki modul Product Catalog di `app/Core`, `app/Ports`, `app/Adapters`, dan belum memiliki migration `products`.
- Kontrak bisnis product master yang dikunci pada halaman ini:
  - `id` internal = UUID string
  - `kode_barang` = opsional, tidak unique secara umum
  - `nama_barang` = wajib
  - `merek` = wajib
  - `ukuran` = opsional, angka bebas
  - `harga_jual` = wajib, integer rupiah, `> 0`
  - stok/jumlah bukan bagian product master
- Makna bisnis `harga_jual` yang dikunci:
  - `harga_jual` adalah batas minimum harga jual
  - penjualan di atas nilai itu tetap boleh
- Rule duplicate minimum yang dikunci:
  - jika `ukuran` terisi, exact duplicate `nama_barang + merek + ukuran` tidak boleh
  - jika `ukuran` kosong, `nama_barang + merek` yang sama dianggap duplikat
  - exception hanya jika kedua record sama-sama punya `kode_barang` dan nilainya berbeda
- `kode_barang` bukan identitas utama sistem; identitas utama product master adalah `id` UUID internal.
- Jalur transport untuk slice ini tetap memakai `routes/web.php`; tidak dilakukan refactor split route.
- Setelah implementasi halaman ini, create/update product master sudah hidup end-to-end minimum dan sudah terbukti lewat feature test.

### Output wajib Step 4 yang sudah terbukti pada halaman ini

- `harga_jual` minimum tervalidasi

### Output wajib Step 4 yang belum terbukti pada halaman ini

- product baru tidak bisa lahir dari supplier invoice

## Scope yang Dipakai [SCOPE-IN]

- kontrak minimum product master
- create product master
- update product master
- duplicate guard minimum untuk product master
- validasi `harga_jual > 0`
- migration `products`
- feature tests untuk create/update product master

## Scope yang Tidak Dipakai [SCOPE-OUT]

- supplier invoice flow
- validasi supplier terhadap product master
- stok/mutasi stok
- seeder produk
- refactor pemisahan route (admin / kasir / dll.)
- UI/filter/search untuk pemilihan product

## Keputusan yang Dikunci [DECISION]

- Implementasi Step 4 dipecah bertahap dengan urutan aman:
  1. migration + core + ports
  2. handler + adapter DB
  3. request + controller + provider + routes
  4. tests
- product master memakai `id` UUID string internal yang dibuat sistem melalui `UuidPort`.
- `harga_jual` disimpan sebagai integer rupiah dan harus `> 0`.
- stok/jumlah dipisah dari product master.
- Route Step 4 tetap berada di `routes/web.php`; isu split route defer dan bukan scope halaman ini.
- Tests diprioritaskan pada slice aktif; seeder defer.

## File yang Dibuat / Diubah [FILES]

### File baru

- `database/migrations/2026_03_11_000100_create_products_table.php`
- `app/Core/ProductCatalog/Product/Product.php`
- `app/Ports/Out/ProductCatalog/ProductReaderPort.php`
- `app/Ports/Out/ProductCatalog/ProductWriterPort.php`
- `app/Ports/Out/ProductCatalog/ProductDuplicateCheckerPort.php`
- `app/Application/ProductCatalog/UseCases/CreateProductHandler.php`
- `app/Application/ProductCatalog/UseCases/UpdateProductHandler.php`
- `app/Adapters/Out/ProductCatalog/DatabaseProductReaderAdapter.php`
- `app/Adapters/Out/ProductCatalog/DatabaseProductWriterAdapter.php`
- `app/Adapters/Out/ProductCatalog/DatabaseProductDuplicateCheckerAdapter.php`
- `app/Adapters/In/Http/Requests/ProductCatalog/CreateProductRequest.php`
- `app/Adapters/In/Http/Requests/ProductCatalog/UpdateProductRequest.php`
- `app/Adapters/In/Http/Controllers/ProductCatalog/CreateProductController.php`
- `app/Adapters/In/Http/Controllers/ProductCatalog/UpdateProductController.php`
- `tests/Feature/ProductCatalog/CreateProductFeatureTest.php`
- `tests/Feature/ProductCatalog/UpdateProductFeatureTest.php`

### File diubah

- `app/Providers/HexagonalServiceProvider.php`
- `routes/web.php`

## Bukti Verifikasi [PROOF]

### 1) Syntax check — migration, core, ports

**Command:**

- `php -l database/migrations/2026_03_11_000100_create_products_table.php`
- `php -l app/Core/ProductCatalog/Product/Product.php`
- `php -l app/Ports/Out/ProductCatalog/ProductReaderPort.php`
- `php -l app/Ports/Out/ProductCatalog/ProductWriterPort.php`
- `php -l app/Ports/Out/ProductCatalog/ProductDuplicateCheckerPort.php`

**Hasil:**

- semua PASS / No syntax errors detected

### 2) Syntax check — application dan adapter out

**Command:**

- `php -l app/Application/ProductCatalog/UseCases/CreateProductHandler.php`
- `php -l app/Application/ProductCatalog/UseCases/UpdateProductHandler.php`
- `php -l app/Adapters/Out/ProductCatalog/DatabaseProductReaderAdapter.php`
- `php -l app/Adapters/Out/ProductCatalog/DatabaseProductWriterAdapter.php`
- `php -l app/Adapters/Out/ProductCatalog/DatabaseProductDuplicateCheckerAdapter.php`

**Hasil:**

- semua PASS / No syntax errors detected

### 3) Syntax check — request, controller, provider, route

**Command:**

- `php -l app/Adapters/In/Http/Requests/ProductCatalog/CreateProductRequest.php`
- `php -l app/Adapters/In/Http/Requests/ProductCatalog/UpdateProductRequest.php`
- `php -l app/Adapters/In/Http/Controllers/ProductCatalog/CreateProductController.php`
- `php -l app/Adapters/In/Http/Controllers/ProductCatalog/UpdateProductController.php`
- `php -l app/Providers/HexagonalServiceProvider.php`
- `php -l routes/web.php`

**Hasil:**

- semua PASS / No syntax errors detected

### 4) Verifikasi route

**Command:**

- `php artisan route:list | grep product-catalog`

**Hasil:**

- route create terdaftar:
  - `POST product-catalog/products/create`
- route update terdaftar:
  - `POST product-catalog/products/{productId}/update`

### 5) Feature test — create

**Command:**

- `php artisan test tests/Feature/ProductCatalog/CreateProductFeatureTest.php`

**Hasil:**

- PASS
- `4 passed`
- `8 assertions`

### 6) Feature test — update

**Command:**

- `php artisan test tests/Feature/ProductCatalog/UpdateProductFeatureTest.php`

**Hasil:**

- PASS
- `3 passed`
- `6 assertions`

## Blocker Aktif [BLOCKER]

- tidak ada blocker aktif untuk sub-slice Step 4a

### Blocker untuk penutupan Step 4 induk

- validasi supplier terhadap product master belum dikerjakan, sehingga output wajib **“product baru tidak bisa lahir dari supplier invoice”** belum terbukti

## State Repo yang Penting untuk Langkah Berikutnya

- Tabel `products` sudah ada sebagai source of truth awal product master.
- Create/update product master sudah hidup end-to-end minimum lewat route web.
- Provider binding Step 4 untuk `ProductReaderPort`, `ProductWriterPort`, dan `ProductDuplicateCheckerPort` sudah aktif.
- Feature tests untuk create/update product master sudah PASS.
- stok/jumlah tetap belum menjadi bagian product master.
- Seeder produk belum dibuat dan sengaja defer.
- Route split lintas area belum diubah dan bukan bagian slice ini.

## Next Step Paling Aman [NEXT]

Buka sub-slice berikutnya dari Step 4: validasi supplier flow terhadap product master agar terbukti product baru tidak bisa lahir dari supplier invoice.

## Catatan Masuk Halaman Berikutnya

Saat membuka halaman kerja berikutnya, bawa minimal:

- file handoff ini
- `docs/setting_control/first_in.md`
- `docs/setting_control/ai_contract.md`
- referensi docs yang relevan saja
- snapshot file/output terbaru bila diperlukan

---

# Ringkasan Singkat Siap Tempel

Gunakan blok ini untuk dibawa ke halaman berikutnya.

## Ringkasan

### Target

menutup sub-slice Step 4a untuk create/update product master

### Status

selesai untuk Step 4a

### Progres

- 100% untuk Step 4a
- 60% untuk Step 4 induk

### Hasil utama

- migration `products` ada
- entity/ports/handlers/adapters/request/controller/routes untuk create/update product master ada
- `harga_jual` minimum tervalidasi
- duplicate rule minimum tervalidasi
- feature tests create/update PASS

### Next step

- validasi supplier flow terhadap product master

## Jangan Dibuka Ulang

- kontrak product master yang sudah dikunci
- stok/jumlah bukan bagian product master
- `harga_jual` adalah batas minimum harga jual
- route split bukan scope Step 4a
- seeder defer

## Data minimum bila ingin lanjut

- handoff ini
- referensi Step 4 yang relevan
- snapshot area supplier/procurement yang benar-benar ada di repo
- output command / isi file supplier-related bila memang ada
