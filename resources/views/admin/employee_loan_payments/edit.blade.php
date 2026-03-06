<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Payment</title>
</head>
<body>
  <h1>Edit Payment</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  <p>
    Karyawan: <b>{{ $loan->employee?->name }}</b><br>
    Loan ID: <b>{{ $loan->id }}</b>
  </p>

  <form method="POST" action="{{ route('admin.employee_loan_payments.update', [$loan->id, $payment->id]) }}">
    @csrf
    @method('PUT')

    <div style="margin-top:8px;">
      <label>Tanggal bayar</label><br>
      <input type="date" name="paid_at" value="{{ old('paid_at', $payment->paid_at?->toDateString() ?? '') }}" required>
    </div>

    <div style="margin-top:8px;">
      <label>Amount (akan divalidasi <= remaining)</label><br>
      <input type="number" name="amount" value="{{ old('amount', $payment->amount) }}" min="0" required>
    </div>

    <div style="margin-top:8px;">
      <label>Note (wajib)</label><br>
      <textarea name="note" required minlength="3" rows="4" cols="50">{{ old('note', $payment->note) }}</textarea>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Update</button>
      <a href="{{ route('admin.employee_loan_payments.index', $loan->id) }}">Kembali</a>
    </div>
  </form>
</body>
</html>
