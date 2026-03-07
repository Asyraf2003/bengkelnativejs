@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Buat Draft Transaksi</h1>

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.transactions.store') }}">
        @csrf

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nama Customer</label>
                    <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal Transaksi</label>
                    <input type="date" name="transacted_at" class="form-control" value="{{ old('transacted_at', now()->toDateString()) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="note" class="form-control">{{ old('note') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Line 1</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Jenis</label>
                    <select name="lines[0][kind]" class="form-select" required>
                        <option value="product_sale">product_sale</option>
                        <option value="service_fee">service_fee</option>
                        <option value="service_product">service_product</option>
                        <option value="outside_cost">outside_cost</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Produk</label>
                    <select name="lines[0][product_id]" class="form-select">
                        <option value="">-- pilih produk --</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->code }} - {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Qty</label>
                    <input type="number" name="lines[0][qty]" min="1" class="form-control" value="{{ old('lines.0.qty') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" name="lines[0][amount]" min="0" class="form-control" value="{{ old('lines.0.amount', 0) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan Line</label>
                    <textarea name="lines[0][note]" class="form-control">{{ old('lines.0.note') }}</textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Draft</button>
    </form>
</div>
@endsection
