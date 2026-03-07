@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Detail Faktur Supplier #{{ $invoice->id }}</h1>

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

    <div class="mb-3 d-flex gap-2 flex-wrap align-items-end">
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">Kembali ke List</a>

        <a href="{{ route('admin.invoices.proofs.index', $invoice) }}" class="btn btn-secondary">
            Proofs
        </a>

        @if (! $invoice->is_paid)
            <form method="POST" action="{{ route('admin.invoices.mark_paid', $invoice) }}" class="d-flex gap-2 align-items-end">
                @csrf
                <div>
                    <label for="paid_at" class="form-label mb-1">Tanggal Bayar</label>
                    <input
                        type="date"
                        id="paid_at"
                        name="paid_at"
                        class="form-control"
                        value="{{ old('paid_at', now()->toDateString()) }}"
                        required
                    >
                </div>
                <button type="submit" class="btn btn-success">Mark Paid</button>
            </form>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header">Header Faktur</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <strong>ID</strong>
                    <div>{{ $invoice->id }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Invoice No</strong>
                    <div>{{ $invoice->invoice_no }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Supplier</strong>
                    <div>{{ $invoice->supplier_name }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Delivered At</strong>
                    <div>{{ $invoice->delivered_at?->toDateString() }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Due At</strong>
                    <div>{{ $invoice->due_at?->toDateString() }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Status</strong>
                    <div>{{ $invoice->is_paid ? 'Paid' : 'Unpaid' }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Paid At</strong>
                    <div>{{ $invoice->paid_at?->toDateString() ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Grand Total</strong>
                    <div>{{ number_format((int) $invoice->grand_total, 0, ',', '.') }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Jumlah Proof</strong>
                    <div>{{ $invoice->media->count() }}</div>
                </div>

                <div class="col-md-12">
                    <strong>Catatan</strong>
                    <div>{{ $invoice->note ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Item Faktur</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Produk</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>
                                    @if ($item->product)
                                        {{ $item->product->code }} - {{ $item->product->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((int) $item->qty, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int) $item->unit_cost, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int) $item->total_cost, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Grand Total</th>
                            <th class="text-end">{{ number_format((int) $invoice->grand_total, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
