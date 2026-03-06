<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Karyawan</title>
</head>
<body>
  <h1>Karyawan</h1>

  @if (session('status'))
    <div style="color:green;">{{ session('status') }}</div>
  @endif

  <div style="margin:12px 0;">
    <a href="{{ route('admin.employees.create') }}">+ Tambah Karyawan</a>
  </div>

  <form method="GET" action="{{ route('admin.employees.index') }}">
    <input name="q" value="{{ $q }}" placeholder="search nama">
    <button type="submit">Cari</button>
  </form>

  <table border="1" cellpadding="6" cellspacing="0" style="margin-top:12px;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nama</th>
        <th>Dibuat</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $r)
        <tr>
          <td>{{ $r->id }}</td>
          <td>{{ $r->name }}</td>
          <td>{{ $r->created_at }}</td>
          <td>
            <a href="{{ route('admin.employees.edit', $r->id) }}">Edit</a>
            <form method="POST" action="{{ route('admin.employees.delete', $r->id) }}" style="display:inline;">
              @csrf
              <button type="submit" onclick="return confirm('Hapus karyawan?')">Delete</button>
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
