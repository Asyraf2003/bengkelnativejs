<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Stock Adjustment</title>
</head>
<body>
  <h1>Stock Adjustment</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('admin.inventory.adjustments.store') }}">
    @csrf

    <div>
      <label>Produk</label><br>
      <select name="product_id" required>
        <option value="">-- pilih --</option>
        @foreach ($products as $p)
          <option value="{{ $p->id }}">
            {{ $p->code }} — {{ $p->name }} ({{ $p->brand }} / {{ $p->size }})
          </option>
        @endforeach
      </select>
    </div>

    <div style="margin-top:10px;">
      <label>Qty delta</label><br>
      <input type="number" name="qty_delta" required placeholder="contoh: 10 atau -3">
    </div>

    <div style="margin-top:10px;">
      <label>Alasan (wajib)</label><br>
      <textarea name="reason" required minlength="3" rows="4" cols="50"></textarea>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Simpan</button>
      <a href="{{ route('admin.products.index') }}">Kembali</a>
    </div>
  </form>
</body>
</html>
