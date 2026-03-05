<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; }
        nav section { margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
        h2 { margin-top: 0; font-size: 1.2rem; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        ul { list-style: none; padding: 0; }
        li { margin-bottom: 8px; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
        .btn-logout { background: #dc3545; color: white; border: none; padding: 8px 15px; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>

    <header>
        <h1>Dashboard Admin</h1>
        <p>Selamat datang, <strong>{{ auth()->user()->username }}</strong></p>
    </header>

    <nav>
        <section>
            <h2>📦 Manajemen Produk</h2>
            <ul>
                <li><a href="{{ route('admin.products.index') }}">Daftar Semua Produk</a></li>
                <li><a href="{{ route('admin.products.create') }}">Tambah Produk Baru (+)</a></li>
            </ul>
        </section>

        <section>
            <h2>🛠️ Inventaris & Stok</h2>
            <ul>
                <li><a href="{{ route('admin.inventory.adjustments.create') }}">Buat Penyesuaian Stok (Adjustment)</a></li>
                </ul>
        </section>

        <section>
            <h2>🔒 Keamanan</h2>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
                    Logout dari Sistem
                </button>
            </form>
        </section>
    </nav>

    <footer>
        <small>Audit Status: Sistem Berjalan (Laravel 11+ Structure)</small>
    </footer>

</body>
</html>
