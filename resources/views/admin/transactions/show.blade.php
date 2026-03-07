@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Detail Transaksi #{{ $transaction->id }}</h1>

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
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline-secondary">Kembali ke List</a>

        @if ($transaction->status === 'draft')
            <form method="POST" action="{{ route('admin.transactions.mark_paid', $transaction) }}">
                @csrf
                <input type="hidden" name="paid_at" value="{{ now()->toDateString() }}">
                <button type="submit" class="btn btn-success">Mark Paid</button>
            </form>

            <form method="POST" action="{{ route('admin.transactions.cancel', $transaction) }}">
                @csrf
                <button type="submit" class="btn btn-danger">Cancel Draft</button>
            </form>
        @endif

        @if ($transaction->status === 'paid')
            @php
                $hasRefundableStockLine = $transaction->lines->contains(fn ($line) => in_array($line->kind, ['product_sale', 'service_product'], true));
            @endphp

            @if ($hasRefundableStockLine)
                <a href="{{ route('admin.transactions.refund', $transaction) }}" class="btn btn-warning">Refund</a>
            @endif
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header">Header Transaksi</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <strong>ID</strong>
                    <div>{{ $transaction->id }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Customer</strong>
                    <div>{{ $transaction->customer_name }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Status</strong>
                    <div>{{ $transaction->status }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Transacted At</strong>
                    <div>{{ $transaction->transacted_at?->toDateString() }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Paid At</strong>
                    <div>{{ $transaction->paid_at?->toDateString() ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Refunded At</strong>
                    <div>{{ $transaction->refunded_at?->toDateString() ?? '-' }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Refund Amount</strong>
                    <div>{{ number_format((int) $transaction->refund_amount, 0, ',', '.') }}</div>
                </div>
                <div class="col-md-8">
                    <strong>Catatan Transaksi</strong>
                    <div>{{ $transaction->note ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Line Items</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Line ID</th>
                            <th>Jenis</th>
                            <th>Produk</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">COGS</th>
                            <th class="text-end">Sale Unit Cost</th>
                            <th class="text-end">Refunded Qty</th>
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
                                <td class="text-end">{{ $line->cogs_amount !== null ? number_format((int) $line->cogs_amount, 0, ',', '.') : '-' }}</td>
                                <td class="text-end">{{ $line->sale_unit_cost !== null ? number_format((int) $line->sale_unit_cost, 0, ',', '.') : '-' }}</td>
                                <td class="text-end">{{ number_format((int) $line->refunded_qty, 0, ',', '.') }}</td>
                                <td>{{ $line->note ?: '-' }}</td>

                                @if ($transaction->status === 'draft')
                                    <td>
                                        @if ($transaction->lines->count() > 1)
                                            <form method="POST" action="{{ route('admin.transactions.lines.delete', [$transaction, $line]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus Line</button>
                                            </form>
                                        @else
                                            <span class="text-muted">Line terakhir</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $transaction->status === 'draft' ? 10 : 9 }}" class="text-center">Tidak ada line.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
