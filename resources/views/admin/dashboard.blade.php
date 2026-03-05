<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard</title>
</head>
<body>

    <h1>Dashboard Admin</h1>
    <p>User: {{ auth()->user()->username }}</p>

    <hr>

    <h3>Produk</h3>
    <ul>
        <li>
            <a href="{{ route('admin.products.index') }}">Daftar Produk</a>
        </li>
        <li>
            <a href="{{ route('admin.products.create') }}">Tambah Produk</a>
        </li>
    </ul>

    <h3>Inventaris</h3>
    <ul>
        <li>
            <a href="{{ route('admin.inventory.adjustments.create') }}">Penyesuaian Stok</a>
        </li>
    </ul>

    <hr>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>

</body>
</html>
