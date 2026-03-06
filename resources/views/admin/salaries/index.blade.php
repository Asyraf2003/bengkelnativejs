<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gaji</title>
</head>
<body>
  <h1>Gaji</h1>

  @if (session('status'))
    <div style="color:green;">{{ session('status') }}</div>
  @endif

  <div style="margin:12px 0;">
    <a href="{{ route('admin.salaries.create') }}">+ Tambah Gaji</a>
  </div>

  <form method="GET" action="{{ route('admin.salaries.index') }}">
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
        <th>Note</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $r)
        <tr>
          <td>{{ $r->id }}</td>
          <td>{{ $r->paid_at }}</td>
          <td>{{ $r->employee?->name }}</td>
          <td>{{ $r->amount }}</td>
          <td>{{ $r->note }}</td>
          <td>
            <a href="{{ route('admin.salaries.edit', $r->id) }}">Edit</a>
            <form method="POST" action="{{ route('admin.salaries.delete', $r->id) }}" style="display:inline;">
              @csrf
              <button type="submit" onclick="return confirm('Hapus data gaji?')">Delete</button>
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
