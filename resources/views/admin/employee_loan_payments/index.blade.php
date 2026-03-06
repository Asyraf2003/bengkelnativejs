<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pembayaran Hutang</title>
</head>
<body>
  <h1>Pembayaran Hutang</h1>

  @if (session('status'))
    <div style="color:green;">{{ session('status') }}</div>
  @endif

  @php
    $paid = (int)($loan->paid_total ?? 0);
    $remaining = (int)$loan->amount - $paid;
  @endphp

  <p>
    Karyawan: <b>{{ $loan->employee?->name }}</b><br>
    Loan ID: <b>{{ $loan->id }}</b> | Loaned at: <b>{{ $loan->loaned_at }}</b><br>
    Amount: <b>{{ $loan->amount }}</b> | Paid: <b>{{ $paid }}</b> | Remaining: <b>{{ $remaining }}</b>
  </p>

  <div style="margin:12px 0;">
    <a href="{{ route('admin.employee_loan_payments.create', $loan->id) }}">+ Tambah Payment</a>
    &nbsp;|&nbsp;
    <a href="{{ route('admin.employee_loans.index') }}">Kembali ke daftar pinjaman</a>
  </div>

  <table border="1" cellpadding="6" cellspacing="0" style="margin-top:12px;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Tanggal</th>
        <th>Amount</th>
        <th>Note</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($payments as $p)
        <tr>
          <td>{{ $p->id }}</td>
          <td>{{ $p->paid_at }}</td>
          <td>{{ $p->amount }}</td>
          <td>{{ $p->note }}</td>
          <td>
            <a href="{{ route('admin.employee_loan_payments.edit', [$loan->id, $p->id]) }}">Edit</a>
            <form method="POST" action="{{ route('admin.employee_loan_payments.delete', [$loan->id, $p->id]) }}" style="display:inline;">
              @csrf
              <button type="submit" onclick="return confirm('Hapus payment?')">Delete</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div style="margin-top:12px;">
    {{ $payments->links() }}
  </div>
</body>
</html>
