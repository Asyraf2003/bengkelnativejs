<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Payment</title>
</head>
<body>
  <h1>Tambah Payment</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  @php
    $paid = (int)($loan->paid_total ?? 0);
    $remaining = (int)$loan->amount - $paid;
  @endphp

  <p>
    Karyawan: <b>{{ $loan->employee?->name }}</b><br>
    Remaining: <b>{{ $remaining }}</b>
  </p>

  <form method="POST" action="{{ route('admin.employee_loan_payments.store', $loan->id) }}">
    @csrf

    <div style="margin-top:8px;">
      <label>Tanggal bayar</label><br>
      <input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Amount (tidak boleh > remaining)</label><br>
      <input type="number" name="amount" value="{{ old('amount', 0) }}" min="0" required>
    </div>

    <div style="margin-top:8px;">
      <label>Note (wajib)</label><br>
      <textarea name="note" required minlength="3" rows="4" cols="50">{{ old('note') }}</textarea>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Simpan</button>
      <a href="{{ route('admin.employee_loan_payments.index', $loan->id) }}">Kembali</a>
    </div>
  </form>
</body>
</html>
