<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Karyawan</title>
</head>
<body>
  <h1>Tambah Karyawan</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('admin.employees.store') }}">
    @csrf

    <div>
      <label>Nama</label><br>
      <input name="name" value="{{ old('name') }}" required>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Simpan</button>
      <a href="{{ route('admin.employees.index') }}">Kembali</a>
    </div>
  </form>
</body>
</html>
