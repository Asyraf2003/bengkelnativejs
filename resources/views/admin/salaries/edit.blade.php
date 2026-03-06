<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Gaji</title>
</head>
<body>
  <h1>Edit Gaji</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('admin.salaries.update', $salary->id) }}">
    @csrf
    @method('PUT')

    <div>
      <label>Karyawan</label><br>
      <select name="employee_id" required>
        <option value="">-- pilih --</option>
        @foreach ($employees as $e)
          <option value="{{ $e->id }}" {{ (int)old('employee_id', $salary->employee_id) === (int)$e->id ? 'selected' : '' }}>
            {{ $e->name }}
          </option>
        @endforeach
      </select>
    </div>

    <div style="margin-top:8px;">
      <label>Tanggal bayar</label><br>
      <input type="date" name="paid_at" value="{{ old('paid_at', $salary->paid_at?->toDateString() ?? '') }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Amount (rupiah)</label><br>
      <input type="number" name="amount" value="{{ old('amount', $salary->amount) }}" min="0" required>
    </div>

    <div style="margin-top:8px;">
      <label>Note (wajib)</label><br>
      <textarea name="note" required minlength="3" rows="4" cols="50">{{ old('note', $salary->note) }}</textarea>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Update</button>
      <a href="{{ route('admin.salaries.index') }}">Kembali</a>
    </div>
  </form>
</body>
</html>
