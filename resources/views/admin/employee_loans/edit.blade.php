<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Pinjaman</title>
</head>
<body>
  <h1>Edit Pinjaman</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  @php
    $paid = (int)($loan->paid_total ?? 0);
  @endphp
  <p>Paid total: <b>{{ $paid }}</b></p>

  <form method="POST" action="{{ route('admin.employee_loans.update', $loan->id) }}">
    @csrf
    @method('PUT')

    <div>
      <label>Karyawan</label><br>
      <select name="employee_id" required>
        <option value="">-- pilih --</option>
        @foreach ($employees as $e)
          <option value="{{ $e->id }}" {{ (int)old('employee_id', $loan->employee_id) === (int)$e->id ? 'selected' : '' }}>
            {{ $e->name }}
          </option>
        @endforeach
      </select>
    </div>

    <div style="margin-top:8px;">
      <label>Tanggal pinjam</label><br>
      <input type="date" name="loaned_at" value="{{ old('loaned_at', $loan->loaned_at?->toDateString() ?? '') }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Amount (tidak boleh < paid total)</label><br>
      <input type="number" name="amount" value="{{ old('amount', $loan->amount) }}" min="0" required>
    </div>

    <div style="margin-top:8px;">
      <label>Note (wajib)</label><br>
      <textarea name="note" required minlength="3" rows="4" cols="50">{{ old('note', $loan->note) }}</textarea>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Update</button>
      <a href="{{ route('admin.employee_loans.index') }}">Kembali</a>
    </div>
  </form>
</body>
</html>
