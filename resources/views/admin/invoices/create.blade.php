@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Buat Faktur Supplier</h1>

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.invoices.store') }}">
        @csrf

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Invoice No</label>
                    <input type="text" name="invoice_no" class="form-control" value="{{ old('invoice_no') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Supplier Name</label>
                    <input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Delivered At</label>
                    <input type="date" name="delivered_at" class="form-control" value="{{ old('delivered_at', now()->toDateString()) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Due At (opsional)</label>
                    <input type="date" name="due_at" class="form-control" value="{{ old('due_at') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea name="note" class="form-control">{{ old('note') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Item 1</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Produk</label>
                    <select name="items[0][product_id]" class="form-select" required>
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
                    <input type="number" name="items[0][qty]" min="1" class="form-control" value="{{ old('items.0.qty', 1) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Total Cost</label>
                    <input type="number" name="items[0][total_cost]" min="1" class="form-control" value="{{ old('items.0.total_cost', 0) }}" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Faktur</button>
    </form>
</div>
@endsection
