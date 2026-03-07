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

    <h3>Pengeluaran Operasional</h3>
    <ul>
        <li>
            <a href="{{ route('admin.operational_expenses.index') }}">Daftar Pengeluaran Operasional</a>
        </li>
        <li>
            <a href="{{ route('admin.operational_expenses.create') }}">Tambah Pengeluaran Operasional</a>
        </li>
    </ul>

    <h3>Karyawan</h3>
    <ul>
        <li>
            <a href="{{ route('admin.employees.index') }}">Daftar Karyawan</a>
        </li>
        <li>
            <a href="{{ route('admin.employees.create') }}">Tambah Karyawan</a>
        </li>
    </ul>

    <h3>Gaji</h3>
    <ul>
        <li>
            <a href="{{ route('admin.salaries.index') }}">Daftar Gaji</a>
        </li>
        <li>
            <a href="{{ route('admin.salaries.create') }}">Tambah Gaji</a>
        </li>
    </ul>

    <h3>Pinjaman Karyawan</h3>
    <ul>
        <li>
            <a href="{{ route('admin.employee_loans.index') }}">Daftar Pinjaman Karyawan</a>
        </li>
        <li>
            <a href="{{ route('admin.employee_loans.create') }}">Tambah Pinjaman Karyawan</a>
        </li>
    </ul>

    <h3>Transaksi</h3>
    <ul>
        <li>
            <a href="{{ route('admin.transactions.index') }}">Daftar Transaksi</a>
        </li>
        <li>
            <a href="{{ route('admin.transactions.create') }}">Buat Transaksi</a>
        </li>
    </ul>

    <h3>Laporan</h3>
    <ul>
        <li>
            <a href="{{ route('admin.reports.daily_profit') }}">Laporan Laba Harian</a>
        </li>
        <li>
            <a href="{{ route('admin.reports.monthly_profit') }}">Laporan Laba Bulanan</a>
        </li>
        <li>
            <a href="{{ route('admin.reports.stock') }}">Laporan Stok</a>
        </li>
        <li>
            <a href="{{ route('admin.reports.invoice_due_soon') }}">Invoice Jatuh Tempo</a>
        </li>
    </ul>

    <hr>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>

</body>
</html>
