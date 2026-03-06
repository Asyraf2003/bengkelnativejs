<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Operasional</title>
</head>
<body>
  <h1>Operasional</h1>

  @if (session('status'))
    <div style="color:green;">{{ session('status') }}</div>
  @endif

  <div style="margin:12px 0;">
    <a href="{{ route('admin.operational_expenses.create') }}">+ Tambah</a>
  </div>

  <form method="GET" action="{{ route('admin.operational_expenses.index') }}">
    <label>Dari: <input type="date" name="from" value="{{ $from }}"></label>
    <label>Sampai: <input type="date" name="to" value="{{ $to }}"></label>
    <input name="q" value="{{ $q }}" placeholder="search name/note">
    <button type="submit">Filter</button>
  </form>

  <table border="1" cellpadding="6" cellspacing="0" style="margin-top:12px;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Tanggal</th>
        <th>Nama</th>
        <th>Amount</th>
        <th>Note</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $r)
        <tr>
          <td>{{ $r->id }}</td>
          <td>{{ $r->spent_at }}</td>
          <td>{{ $r->name }}</td>
          <td>{{ $r->amount }}</td>
          <td>{{ $r->note }}</td>
          <td>
            <a href="{{ route('admin.operational_expenses.edit', $r->id) }}">Edit</a>
            <form method="POST" action="{{ route('admin.operational_expenses.delete', $r->id) }}" style="display:inline;">
              @csrf
              <button type="submit" onclick="return confirm('Hapus?')">Delete</button>
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
