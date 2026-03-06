<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pinjaman Karyawan</title>
</head>
<body>
  <h1>Pinjaman Karyawan</h1>

  @if (session('status'))
    <div style="color:green;">{{ session('status') }}</div>
  @endif

  <div style="margin:12px 0;">
    <a href="{{ route('admin.employee_loans.create') }}">+ Tambah Pinjaman</a>
  </div>

  <form method="GET" action="{{ route('admin.employee_loans.index') }}">
    <label>Dari: <input type="date" name="from" value="{{ $from }}"></label>
    <label>Sampai: <input type="date" name="to" value="{{ $to }}"></label>

    <label>
      Karyawan:
      <select name="employee_id">
        <option value="">-- semua --</option>
        @foreach ($employees as $e)
          <option value="{{ $e->id }}" {{ (string)$employeeId === (string)$e->id ? 'selected' : '' }}>
            {{ $e->name }}
          </option>
        @endforeach
      </select>
    </label>

    <button type="submit">Filter</button>
  </form>

  <table border="1" cellpadding="6" cellspacing="0" style="margin-top:12px;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Tanggal</th>
        <th>Karyawan</th>
        <th>Amount</th>
        <th>Paid total</th>
        <th>Remaining</th>
        <th>Note</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $r)
        @php
          $paid = (int)($r->paid_total ?? 0);
          $remaining = (int)$r->amount - $paid;
        @endphp
        <tr>
          <td>{{ $r->id }}</td>
          <td>{{ $r->loaned_at }}</td>
          <td>{{ $r->employee?->name }}</td>
          <td>{{ $r->amount }}</td>
          <td>{{ $paid }}</td>
          <td>{{ $remaining }}</td>
          <td>{{ $r->note }}</td>
          <td>
            <a href="{{ route('admin.employee_loan_payments.index', $r->id) }}">Payments</a>
            &nbsp;|&nbsp;
            <a href="{{ route('admin.employee_loans.edit', $r->id) }}">Edit</a>
            <form method="POST" action="{{ route('admin.employee_loans.delete', $r->id) }}" style="display:inline;">
              @csrf
              <button type="submit" onclick="return confirm('Hapus pinjaman? (payments ikut terhapus)')">Delete</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div style="margin-top:12px;">
    {{ $rows->links() }}
  </div>
</body>
</html>
