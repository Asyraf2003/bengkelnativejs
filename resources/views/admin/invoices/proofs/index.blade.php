<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bukti Bayar Faktur</title>
</head>
<body>
  <h1>Bukti Bayar — Faktur {{ $invoice->invoice_no }}</h1>

  @if (session('status'))
    <div style="color:green;">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div style="color:red;">{{ $errors->first() }}</div>
  @endif

  <h3>Upload bukti (multi-file)</h3>
  <form method="POST" action="{{ route('admin.invoices.proofs.upload', $invoice->id) }}" enctype="multipart/form-data">
    @csrf
    <input type="file" name="proofs[]" multiple required>
    <button type="submit">Upload</button>
  </form>

  <h3 style="margin-top:16px;">Daftar bukti</h3>
  @if ($invoice->media->count() === 0)
    <p>Belum ada bukti.</p>
  @else
    <table border="1" cellpadding="6" cellspacing="0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama file</th>
          <th>Mime</th>
          <th>Size</th>
          <th>Uploaded at</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      @foreach ($invoice->media as $m)
        <tr>
          <td>{{ $m->id }}</td>
          <td>{{ $m->original_name }}</td>
          <td>{{ $m->mime }}</td>
          <td>{{ $m->size }}</td>
          <td>{{ $m->uploaded_at }}</td>
          <td>
            <a href="{{ route('admin.invoices.proofs.download', [$invoice->id, $m->id]) }}">Download</a>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  @endif
</body>
</html>
