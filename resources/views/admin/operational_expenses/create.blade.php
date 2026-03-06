<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Operasional</title>
</head>
<body>
  <h1>Tambah Operasional</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('admin.operational_expenses.store') }}">
    @csrf

    <div>
      <label>Nama</label><br>
      <input name="name" value="{{ old('name') }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Tanggal</label><br>
      <input type="date" name="spent_at" value="{{ old('spent_at', now()->toDateString()) }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Amount (rupiah)</label><br>
      <input type="number" name="amount" value="{{ old('amount', 0) }}" min="0" required>
    </div>

    <div style="margin-top:8px;">
      <label>Note (wajib)</label><br>
      <textarea name="note" required minlength="3" rows="4" cols="50">{{ old('note') }}</textarea>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Simpan</button>
      <a href="{{ route('admin.operational_expenses.index') }}">Kembali</a>
    </div>
  </form>
</body>
</html>
