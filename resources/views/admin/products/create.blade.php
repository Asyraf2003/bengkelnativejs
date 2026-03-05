<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Produk</title>
</head>
<body>
  <h1>Tambah Produk</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('admin.products.store') }}">
    @csrf

    <div>
      <label>Kode (unik)</label><br>
      <input name="code" value="{{ old('code') }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Nama</label><br>
      <input name="name" value="{{ old('name') }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Brand</label><br>
      <input name="brand" value="{{ old('brand') }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Size</label><br>
      <input name="size" value="{{ old('size') }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Harga jual (rupiah)</label><br>
      <input type="number" name="sale_price" value="{{ old('sale_price', 0) }}" min="0" required>
    </div>

    <div style="margin-top:8px;">
      <label>
        <input type="checkbox" name="is_active" value="1" checked>
        Aktif
      </label>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Simpan</button>
      <a href="{{ route('admin.products.index') }}">Kembali</a>
    </div>
  </form>
</body>
</html>
