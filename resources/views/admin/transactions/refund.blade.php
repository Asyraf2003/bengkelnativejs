@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Refund Transaksi #{{ $transaction->id }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.transactions.refund.store', $transaction) }}">
        @csrf

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Tanggal Refund</label>
                    <input type="date" name="refunded_at" class="form-control" value="{{ old('refunded_at', now()->toDateString()) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Refund Amount</label>
                    <input type="number" name="refund_amount" min="0" class="form-control" value="{{ old('refund_amount', 0) }}" required>
                </div>
            </div>
        </div>

        @forelse ($stockLines as $i => $line)
            <div class="card mb-3">
                <div class="card-header">
                    Line #{{ $line->id }} - {{ $line->kind }}
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Produk:</strong> {{ $line->product?->name }}</p>
                    <p class="mb-1"><strong>Qty:</strong> {{ $line->qty }}</p>
                    <p class="mb-3"><strong>Sudah Refund:</strong> {{ $line->refunded_qty }}</p>

                    <input type="hidden" name="items[{{ $i }}][line_id]" value="{{ $line->id }}">

                    <div class="mb-3">
                        <label class="form-label">Qty Refund</label>
                        <input
                            type="number"
                            name="items[{{ $i }}][qty]"
                            min="0"
                            class="form-control"
                            value="{{ old("items.{$i}.qty", 0) }}"
                        >
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-warning">
                Tidak ada line stok yang bisa direfund.
            </div>
        @endforelse

        <button type="submit" class="btn btn-warning">Simpan Refund</button>
    </form>
</div>
@endsection
