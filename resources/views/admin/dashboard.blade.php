<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - System</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; margin: 20px; color: #333; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .card { border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #f9f9f9; }
        .card h3 { margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 5px; color: #2c3e50; }
        ul { padding-left: 20px; }
        li { margin-bottom: 5px; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .logout-btn { background: #e74c3c; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    </style>
</head>
<body>

    <header class="header">
        <div>
            <h1>Dashboard Admin</h1>
            <p>Login sebagai: <strong>{{ auth()->user()->username }}</strong></p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </header>

    <div class="grid">
        
        <div class="card">
            <h3>📦 Produk</h3>
            <ul>
                <li><a href="{{ route('admin.products.index') }}">Daftar Produk</a></li>
                <li><a href="{{ route('admin.products.create') }}">Tambah Produk Baru</a></li>
            </ul>
        </div>

        <div class="card">
            <h3>🏗️ Inventaris</h3>
            <ul>
                <li><a href="{{ route('admin.inventory.adjustments.create') }}">Buat Penyesuaian Stok</a></li>
            </ul>
        </div>

        <div class="card">
            <h3>💰 Transaksi & Penjualan</h3>
            <ul>
                <li><a href="{{ route('admin.transactions.index') }}">Riwayat Transaksi</a></li>
                <li><a href="{{ route('admin.transactions.create') }}">Input Transaksi Baru</a></li>
                <li><a href="{{ route('admin.invoices.index') }}">Daftar Invoice</a></li>
                <li><a href="{{ route('admin.invoices.create') }}">Buat Invoice Baru</a></li>
            </ul>
        </div>

        <div class="card">
            <h3>💸 Operasional</h3>
            <ul>
                <li><a href="{{ route('admin.operational_expenses.index') }}">Daftar Pengeluaran</a></li>
                <li><a href="{{ route('admin.operational_expenses.create') }}">Catat Pengeluaran</a></li>
            </ul>
        </div>

        <div class="card">
            <h3>👥 HR & Karyawan</h3>
            <ul>
                <li><a href="{{ route('admin.employees.index') }}">Data Karyawan</a></li>
                <li><a href="{{ route('admin.employees.create') }}">Tambah Karyawan</a></li>
            </ul>
        </div>

        <div class="card">
            <h3>🏦 Gaji & Pinjaman</h3>
            <ul>
                <li><a href="{{ route('admin.salaries.index') }}">Data Gaji</a></li>
                <li><a href="{{ route('admin.salaries.create') }}">Input Gaji Baru</a></li>
                <li><a href="{{ route('admin.employee_loans.index') }}">Daftar Pinjaman</a></li>
                <li><a href="{{ route('admin.employee_loans.create') }}">Input Pinjaman Baru</a></li>
            </ul>
        </div>

        <div class="card">
            <h3>📊 Laporan (Reporting)</h3>
            <ul>
                <li><a href="{{ route('admin.reports.daily_profit') }}">Laba Harian</a></li>
                <li><a href="{{ route('admin.reports.monthly_profit') }}">Laba Bulanan</a></li>
                <li><a href="{{ route('admin.reports.stock') }}">Laporan Stok Barang</a></li>
                <li><a href="{{ route('admin.reports.invoice_due_soon') }}">Invoice Jatuh Tempo</a></li>
            </ul>
        </div>

    </div>

</body>
</html>
