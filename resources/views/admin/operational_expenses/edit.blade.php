<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Operasional</title>
</head>
<body>
  <h1>Edit Operasional</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('admin.operational_expenses.update', $expense->id) }}">
    @csrf
    @method('PUT')

    <div>
      <label>Nama</label><br>
      <input name="name" value="{{ old('name', $expense->name) }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Tanggal</label><br>
      <input type="date" name="spent_at" value="{{ old('spent_at', $expense->spent_at?->toDateString() ?? '') }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Amount (rupiah)</label><br>
      <input type="number" name="amount" value="{{ old('amount', $expense->amount) }}" min="0" required>
    </div>

    <div style="margin-top:8px;">
      <label>Note (wajib)</label><br>
      <textarea name="note" required minlength="3" rows="4" cols="50">{{ old('note', $expense->note) }}</textarea>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Update</button>
      <a href="{{ route('admin.operational_expenses.index') }}">Kembali</a>
    </div>
  </form>
</body>
</html>
