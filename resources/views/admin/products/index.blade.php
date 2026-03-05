<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk</title>
</head>
<body>
    <h1>Produk</h1>

    @if (session('status'))
        <div style="color:green;">{{ session('status') }}</div>
    @endif

    <div style="margin: 12px 0;">
        <a href="{{ route('admin.inventory.adjustments.create') }}">+ Stock Adjustment</a>
    </div>

    <form method="GET" action="{{ route('admin.products.index') }}">
        <input name="q" value="{{ $q }}" placeholder="search code/name/brand">
        <button type="submit">Cari</button>
    </form>

    <table border="1" cellpadding="6" cellspacing="0" style="margin-top:12px;">
        <thead>
        <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Nama</th>
            <th>Brand</th>
            <th>Size</th>
            <th>Harga</th>
            <th>On hand</th>
            <th>Reserved</th>
            <th>Available</th>
            <th>Avg cost</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($products as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->code }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->brand }}</td>
                <td>{{ $p->size }}</td>
                <td>{{ $p->sale_price }}</td>
                <td>{{ $p->inventory?->on_hand_qty ?? 0 }}</td>
                <td>{{ $p->inventory?->reserved_qty ?? 0 }}</td>
                <td>{{ $p->inventory?->available_qty ?? 0 }}</td>
                <td>{{ $p->inventory?->avg_cost ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top:12px;">
        {{ $products->links() }}
    </div>
</body>
</html>
