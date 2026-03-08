@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Pesanan Pelanggan</h1>

    @if (session('status'))
        <div class="alert alert-success mb-3">{{ session('status') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.customer_orders.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Cari Nama / Catatan</label>
                    <input type="text" name="q" class="form-control" value="{{ $q }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Dibuat Dari</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Dibuat Sampai</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-3 d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.transactions.create') }}" class="btn btn-primary">Buat Pesanan Baru</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Pelanggan</th>
                            <th>Tanggal Dibuat</th>
                            <th>Jumlah Transaksi</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->customer_name }}</td>
                                <td>{{ $row->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $row->transactions_count }}</td>
                                <td>{{ $row->note ?: '-' }}</td>
                                <td>
                                    <a href="{{ route('admin.customer_orders.show', $row) }}" class="btn btn-sm btn-outline-primary">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
