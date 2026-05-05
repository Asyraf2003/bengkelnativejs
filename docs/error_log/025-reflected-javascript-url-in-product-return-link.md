# 025 - Reflected javascript URL in product return link

## Status

Patched, with verification gap.

## Keparahan

High.

## Ringkasan

Halaman `admin.products.create` memiliki reflected, click-triggered XSS melalui parameter `return_to`.

`CreateProductPageController` menerima `return_to` dari query string, lalu sebelumnya hanya melakukan trim/type normalization sebelum mengirim nilainya ke view sebagai `returnTo`.

View `resources/views/admin/products/create.blade.php` merender nilai tersebut ke atribut:

`href="{{ $returnTo }}"`

Blade HTML escaping mencegah quote/markup injection, tetapi tidak memblokir URL scheme berbahaya seperti `javascript:`.

Akibatnya, URL seperti:

`/admin/products/create?return_to=javascript:...&return_label=Kembali`

dapat membuat tombol kembali yang menjalankan JavaScript saat diklik admin.

## Jalur rentan

Attacker membuat URL product create
-> admin yang sudah login membuka URL tersebut
-> `return_to` dibaca dari query string
-> controller hanya trim nilai tersebut
-> nilai dikirim ke view sebagai `returnTo`
-> view merender `href`
-> admin klik tombol kembali
-> `javascript:` berjalan di origin aplikasi
-> script memakai sesi admin dan token/form same-origin untuk membaca data atau mengirim request admin

## Root cause

Nilai URL dari query string dipakai langsung sebagai `href` tanpa allowlist route atau validasi scheme.

Escaping HTML saja tidak cukup untuk konteks URL karena `javascript:` tetap valid sebagai nilai `href`.

## Patch summary

`app/Adapters/In/Http/Controllers/Admin/Product/CreateProductPageController.php` diubah agar `__invoke()` memakai:

`resolveReturnTo($request->query('return_to'))`

bukan trim-only normalization.

Method `resolveReturnTo()` menolak nilai kosong atau tidak dipercaya, termasuk payload `javascript:`.

Nilai yang diterima hanya return URL yang cocok dengan route:

`admin.procurement.supplier-invoices.create`

dalam bentuk absolute atau relative.

## Verification

Reported successful checks:

- `php -l app/Adapters/In/Http/Controllers/Admin/Product/CreateProductPageController.php`
- `git commit -m "Harden product create return link allowlist"`

## Verification gap

Belum ada feature/browser test yang membuktikan payload `javascript:` tidak lagi dirender sebagai href aktif.

Future verification:

- render `/admin/products/create?return_to=javascript:alert(1)&return_label=Kembali`
- pastikan tombol kembali tidak memakai `javascript:` sebagai `href`
- render return URL yang valid dari `admin.procurement.supplier-invoices.create`
- pastikan tombol kembali tetap muncul untuk route yang diizinkan

## Relations

Related to #024.

#024 covers reflected XSS through unsafe JSON config in `admin.expenses.create`.

#025 covers reflected click-triggered XSS through unsafe `href` rendering in `admin.products.create`.

Related to #007 as part of the broader XSS/output-context cluster, but #007 is stored XSS through workspace JSON config while #025 is reflected XSS through a return link URL.
