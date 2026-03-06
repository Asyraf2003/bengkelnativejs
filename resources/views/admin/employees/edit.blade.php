<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Karyawan</title>
</head>
<body>
  <h1>Edit Karyawan</h1>

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('admin.employees.update', $employee->id) }}">
    @csrf
    @method('PUT')

    <div>
      <label>Nama</label><br>
      <input name="name" value="{{ old('name', $employee->name) }}" required>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Update</button>
      <a href="{{ route('admin.employees.index') }}">Kembali</a>
    </div>
  </form>
</body>
</html>
