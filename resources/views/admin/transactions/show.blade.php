@extends('layouts.app')

@section('content')
@php
    $displayStatus = match ($transaction->status) {
        'draft' => 'Belum Lunas',
        'paid' => 'Lunas',
        'canceled' => 'Batal',
        'refunded' => 'Refund',
        default => $transaction->status,
    };
@endphp

<div class="container py-4">
    <h1 class="mb-4">Detail Kasus #{{ $transaction->id }}</h1>

    @if (session('status'))
        <div class="alert alert-success mb-3">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-3 d-flex gap-2 flex-wrap">
        @if ($transaction->customerOrder)
            <a href="{{ route('admin.customer_orders.show', $transaction->customerOrder) }}" class="btn btn-outline-secondary">
                Kembali ke Nota Pelanggan
            </a>
        @else
            <a href="{{ route('admin.customer_orders.index') }}" class="btn btn-outline-secondary">
                Kembali ke Daftar Nota
            </a>
        @endif

        @if ($transaction->status === 'draft')
            <a href="{{ route('admin.transactions.edit', $transaction) }}" class="btn btn-primary">Edit Kasus</a>

            <form method="POST" action="{{ route('admin.transactions.mark_paid', $transaction) }}" class="m-0">
                @csrf
                <input type="hidden" name="paid_at" value="{{ now()->toDateString() }}">
                <button type="submit" class="btn btn-success">Lunaskan</button>
            </form>

            <form method="POST" action="{{ route('admin.transactions.cancel', $transaction) }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-danger">Batalkan</button>
            </form>
        @endif

        @if ($transaction->status === 'paid')
            @php
                $hasRefundableStockLine = $transaction->lines->contains(
                    fn ($line) => in_array($line->kind, ['product_sale', 'service_product'], true)
                );

                $alreadyRefunded =
                    $transaction->refunded_at !== null
                    || (int) $transaction->refund_amount > 0
                    || $transaction->lines->contains(fn ($line) => (int) $line->refunded_qty > 0);
            @endphp

            @if ($hasRefundableStockLine && ! $alreadyRefunded)
                <a href="{{ route('admin.transactions.refund', $transaction) }}" class="btn btn-warning">
                    Refund
                </a>
            @endif
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header">Informasi Kasus</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <strong>ID Kasus</strong>
                    <div>{{ $transaction->id }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Nota Pelanggan</strong>
                    <div>
                        @if ($transaction->customerOrder)
                            <a href="{{ route('admin.customer_orders.show', $transaction->customerOrder) }}">
                                #{{ $transaction->customerOrder->id }}
                            </a>
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <strong>Nama Pelanggan</strong>
                    <div>{{ $transaction->customer_name }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Status</strong>
                    <div>{{ $displayStatus }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Tanggal Kasus</strong>
                    <div>{{ $transaction->transacted_at?->toDateString() ?? '-' }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Tanggal Lunas</strong>
                    <div>{{ $transaction->paid_at?->toDateString() ?? '-' }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Tanggal Refund</strong>
                    <div>{{ $transaction->refunded_at?->toDateString() ?? '-' }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Nominal Refund</strong>
                    <div>{{ number_format((int) $transaction->refund_amount, 0, ',', '.') }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Tanggal Nota Dibuat</strong>
                    <div>{{ $transaction->customerOrder?->created_at?->format('Y-m-d H:i:s') ?? '-' }}</div>
                </div>

                <div class="col-md-12">
                    <strong>Catatan Kasus</strong>
                    <div>{{ $transaction->note ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Daftar Rincian</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID Rincian</th>
                            <th>Jenis</th>
                            <th>Produk</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-end">COGS</th>
                            <th class="text-end">Biaya Modal per Unit</th>
                            <th class="text-end">Qty Refund</th>
                            <th>Catatan</th>
                            @if ($transaction->status === 'draft')
                                <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transaction->lines as $line)
                            <tr>
                                <td>{{ $line->id }}</td>
                                <td>{{ $line->kind }}</td>
                                <td>
                                    @if ($line->product)
                                        {{ $line->product->code }} - {{ $line->product->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">{{ $line->qty ?? '-' }}</td>
                                <td class="text-end">{{ number_format((int) $line->amount, 0, ',', '.') }}</td>
                                <td class="text-end">
                                    {{ $line->cogs_amount !== null ? number_format((int) $line->cogs_amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-end">
                                    {{ $line->sale_unit_cost !== null ? number_format((int) $line->sale_unit_cost, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-end">{{ number_format((int) $line->refunded_qty, 0, ',', '.') }}</td>
                                <td>{{ $line->note ?: '-' }}</td>

                                @if ($transaction->status === 'draft')
                                    <td>
                                        @if ($transaction->lines->count() > 1)
                                            <form method="POST" action="{{ route('admin.transactions.lines.delete', [$transaction, $line]) }}" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus Rincian</button>
                                            </form>
                                        @else
                                            <span class="text-muted">Rincian terakhir</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $transaction->status === 'draft' ? 10 : 9 }}" class="text-center">
                                    Tidak ada rincian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
