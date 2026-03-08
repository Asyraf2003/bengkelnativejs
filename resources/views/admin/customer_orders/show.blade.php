@extends('layouts.app')

@section('content')
@php
    $mapStatus = function (string $status): string {
        return match ($status) {
            'draft' => 'Hutang',
            'paid' => 'Lunas',
            'canceled', 'refunded' => 'Batal',
            default => $status,
        };
    };
@endphp

<div class="container py-4">
    <h1 class="mb-4">Detail Pesanan Pelanggan #{{ $customerOrder->id }}</h1>

    @if (session('status'))
        <div class="alert alert-success mb-3">{{ session('status') }}</div>
    @endif

    <div class="mb-3 d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.customer_orders.index') }}" class="btn btn-outline-secondary">Kembali ke List</a>
        <a href="{{ route('admin.transactions.create', ['customer_order_id' => $customerOrder->id]) }}" class="btn btn-primary">
            Tambah Transaksi
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-header">Header Pesanan Pelanggan</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <strong>ID</strong>
                    <div>{{ $customerOrder->id }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Nama Pelanggan</strong>
                    <div>{{ $customerOrder->customer_name }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Tanggal Dibuat</strong>
                    <div>{{ $customerOrder->created_at?->format('Y-m-d H:i:s') }}</div>
                </div>

                <div class="col-md-12">
                    <strong>Catatan</strong>
                    <div>{{ $customerOrder->note ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Transaksi</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Tanggal Transaksi</th>
                            <th>Status</th>
                            <th>Jumlah Line</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customerOrder->transactions as $transaction)
                            @php
                                $hasRefundableStockLine = (int) $transaction->refundable_stock_lines_count > 0;
                                $alreadyRefunded =
                                    $transaction->refunded_at !== null
                                    || (int) $transaction->refund_amount > 0
                                    || $transaction->lines->contains(fn ($line) => (int) $line->refunded_qty > 0);
                            @endphp

                            <tr>
                                <td>{{ $transaction->id }}</td>
                                <td>{{ $transaction->transacted_at?->toDateString() }}</td>
                                <td>{{ $mapStatus($transaction->status) }}</td>
                                <td>{{ $transaction->lines_count }}</td>
                                <td>{{ $transaction->note ?: '-' }}</td>
                                <td class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                        Detail
                                    </a>

                                    @if ($transaction->status === 'draft')
                                        <a href="{{ route('admin.transactions.edit', $transaction) }}" class="btn btn-sm btn-primary">
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.transactions.mark_paid', $transaction) }}">
                                            @csrf
                                            <input type="hidden" name="paid_at" value="{{ now()->toDateString() }}">
                                            <button type="submit" class="btn btn-sm btn-success">Lunaskan</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.transactions.cancel', $transaction) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger">Batalkan</button>
                                        </form>
                                    @endif

                                    @if ($transaction->status === 'paid' && $hasRefundableStockLine && ! $alreadyRefunded)
                                        <a href="{{ route('admin.transactions.refund', $transaction) }}" class="btn btn-sm btn-warning">
                                            Batalkan / Refund
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
