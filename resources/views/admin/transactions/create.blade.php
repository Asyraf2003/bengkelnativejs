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
                    <label class="form-label">Catatan Transaksi</label>
                    <textarea name="note" class="form-control">{{ old('note') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3" id="transaction-line-card">
            <div class="card-header">Line 1</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Jenis</label>
                    <select name="lines[0][kind]" id="line-kind" class="form-select" required>
                        <option value="product_sale" @selected(old('lines.0.kind') === 'product_sale')>product_sale</option>
                        <option value="service_fee" @selected(old('lines.0.kind') === 'service_fee')>service_fee</option>
                        <option value="service_product" @selected(old('lines.0.kind') === 'service_product')>service_product</option>
                        <option value="outside_cost" @selected(old('lines.0.kind') === 'outside_cost')>outside_cost</option>
                    </select>
                    <div class="form-text">
                        product_sale dan service_product memakai stok produk. service_fee dan outside_cost tetap manual.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Produk</label>
                    <select name="lines[0][product_id]" id="line-product-id" class="form-select">
                        <option value="">-- pilih produk --</option>
                        @foreach ($products as $product)
                            <option
                                value="{{ $product->id }}"
                                data-sale-price="{{ (int) $product->sale_price }}"
                                @selected((string) old('lines.0.product_id') === (string) $product->id)
                            >
                                {{ $product->code }} - {{ $product->name }} (harga jual {{ number_format($product->sale_price, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" id="line-product-price-hint">Harga jual produk akan dipakai sebagai harga awal line stok.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Qty</label>
                    <input type="number" name="lines[0][qty]" id="line-qty" min="1" class="form-control" value="{{ old('lines.0.qty', 1) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Amount (total line)</label>
                    <input type="number" name="lines[0][amount]" id="line-amount" min="0" class="form-control" value="{{ old('lines.0.amount', 0) }}" required>
                    <div class="form-text" id="line-amount-hint">
                        Untuk line stok: default = qty × harga jual produk. Override boleh, tapi tidak boleh di bawah harga jual master.
                    </div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const kindEl = document.getElementById('line-kind');
    const productEl = document.getElementById('line-product-id');
    const qtyEl = document.getElementById('line-qty');
    const amountEl = document.getElementById('line-amount');
    const priceHintEl = document.getElementById('line-product-price-hint');
    const amountHintEl = document.getElementById('line-amount-hint');

    let amountTouched = false;

    function usesStock(kind) {
        return kind === 'product_sale' || kind === 'service_product';
    }

    function selectedSalePrice() {
        const option = productEl.options[productEl.selectedIndex];
        if (!option) {
            return 0;
        }

        return parseInt(option.dataset.salePrice || '0', 10);
    }

    function qtyValue() {
        const qty = parseInt(qtyEl.value || '0', 10);
        return Number.isNaN(qty) || qty < 1 ? 0 : qty;
    }

    function updateHints() {
        const stockMode = usesStock(kindEl.value);
        const salePrice = selectedSalePrice();
        const qty = qtyValue();
        const minimumAmount = salePrice * qty;

        if (stockMode) {
            priceHintEl.textContent = salePrice > 0
                ? `Harga jual master: ${salePrice.toLocaleString('id-ID')}.`
                : 'Pilih produk untuk mengambil harga jual master.';
            amountHintEl.textContent = minimumAmount > 0
                ? `Total minimum line stok = qty × harga jual = ${minimumAmount.toLocaleString('id-ID')}.`
                : 'Isi produk dan qty untuk menghitung total minimum line stok.';
        } else {
            priceHintEl.textContent = 'Line non-stok tidak memakai harga produk.';
            amountHintEl.textContent = 'Untuk service_fee dan outside_cost, amount diisi manual.';
        }
    }

    function autofillAmount(force = false) {
        const stockMode = usesStock(kindEl.value);
        const salePrice = selectedSalePrice();
        const qty = qtyValue();

        if (!stockMode) {
            updateHints();
            return;
        }

        const computed = salePrice * qty;

        if (computed > 0 && (!amountTouched || force)) {
            amountEl.value = computed;
        }

        updateHints();
    }

    kindEl.addEventListener('change', function () {
        amountTouched = false;
        autofillAmount(true);
    });

    productEl.addEventListener('change', function () {
        amountTouched = false;
        autofillAmount(true);
    });

    qtyEl.addEventListener('input', function () {
        amountTouched = false;
        autofillAmount(true);
    });

    amountEl.addEventListener('input', function () {
        amountTouched = true;
        updateHints();
    });

    autofillAmount(false);
});
</script>
@endsection
