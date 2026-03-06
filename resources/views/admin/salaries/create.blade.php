<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Gaji</title>
</head>
<body>
  <h1>Tambah Gaji</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('admin.salaries.store') }}">
    @csrf

    <div>
      <label>Karyawan</label><br>
      <select name="employee_id" required>
        <option value="">-- pilih --</option>
        @foreach ($employees as $e)
          <option value="{{ $e->id }}" {{ old('employee_id') == $e->id ? 'selected' : '' }}>
            {{ $e->name }}
          </option>
        @endforeach
      </select>
    </div>

    <div style="margin-top:8px;">
      <label>Tanggal bayar</label><br>
      <input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" required>
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
      <a href="{{ route('admin.salaries.index') }}">Kembali</a>
    </div>
  </form>
</body>
</html>
