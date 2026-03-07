@extends('layouts.app')

@section('content')
@php
    $oldLines = old('lines');

    if (!is_array($oldLines) || count($oldLines) < 1) {
        $oldLines = $transaction->lines->map(fn ($line) => [
            'kind' => (string) $line->kind,
            'product_id' => $line->product_id ? (string) $line->product_id : '',
            'qty' => $line->qty ?? 1,
            'amount' => (int) $line->amount,
            'note' => (string) ($line->note ?? ''),
        ])->values()->all();
    }

    if (!is_array($oldLines) || count($oldLines) < 1) {
        $oldLines = [[
            'kind' => 'product_sale',
            'product_id' => '',
            'qty' => 1,
            'amount' => 0,
            'note' => '',
        ]];
    }

    $productOptions = $products->map(fn ($product) => [
        'id' => (int) $product->id,
        'code' => (string) $product->code,
        'name' => (string) $product->name,
        'sale_price' => (int) $product->sale_price,
    ])->values()->all();
@endphp

<div class="container py-4">
    <h1 class="mb-4">Edit Draft Transaksi #{{ $transaction->id }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.transactions.update', $transaction) }}">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nama Customer</label>
                    <input
                        type="text"
                        name="customer_name"
                        class="form-control"
                        value="{{ old('customer_name', $transaction->customer_name) }}"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal Transaksi</label>
                    <input
                        type="date"
                        name="transacted_at"
                        class="form-control"
                        value="{{ old('transacted_at', $transaction->transacted_at?->toDateString()) }}"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan Transaksi</label>
                    <textarea name="note" class="form-control">{{ old('note', $transaction->note) }}</textarea>
                </div>
            </div>
        </div>

        <div id="transaction-lines-root"></div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn btn-outline-secondary">Kembali</a>
            <button type="button" id="add-line-btn" class="btn btn-outline-secondary">Tambah Line</button>
            <button type="submit" class="btn btn-primary">Update Draft</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($productOptions);
    const initialLines = @json($oldLines);

    const root = document.getElementById('transaction-lines-root');
    const addLineBtn = document.getElementById('add-line-btn');

    function usesStock(kind) {
        return kind === 'product_sale' || kind === 'service_product';
    }

    function productById(productId) {
        const id = parseInt(productId || '0', 10);
        if (!id) {
            return null;
        }

        return products.find(p => p.id === id) || null;
    }

    function buildProductOptions(selectedId) {
        const selected = String(selectedId ?? '');

        let html = '<option value="">-- pilih produk --</option>';

        for (const product of products) {
            const isSelected = selected === String(product.id) ? 'selected' : '';
            html += `
                <option value="${product.id}" data-sale-price="${product.sale_price}" ${isSelected}>
                    ${escapeHtml(product.code)} - ${escapeHtml(product.name)} (harga jual ${formatNumber(product.sale_price)})
                </option>
            `;
        }

        return html;
    }

    function defaultLine() {
        return {
            kind: 'product_sale',
            product_id: '',
            qty: 1,
            amount: 0,
            note: '',
        };
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatNumber(value) {
        return Number(value || 0).toLocaleString('id-ID');
    }

    function render() {
        root.innerHTML = '';

        const cards = state.lines.map((line, index) => buildLineCard(line, index)).join('');
        root.innerHTML = cards;

        bindLineEvents();
        refreshAllHints();
    }

    function buildLineCard(line, index) {
        const stockMode = usesStock(line.kind);
        const product = productById(line.product_id);
        const salePrice = product ? Number(product.sale_price) : 0;
        const qty = parseInt(line.qty || '0', 10) > 0 ? parseInt(line.qty || '0', 10) : 0;
        const minimumAmount = stockMode ? salePrice * qty : 0;

        const productHint = stockMode
            ? (salePrice > 0
                ? `Harga jual master: ${formatNumber(salePrice)}.`
                : 'Pilih produk untuk mengambil harga jual master.')
            : 'Line non-stok tidak memakai harga produk.';

        const amountHint = stockMode
            ? (minimumAmount > 0
                ? `Total minimum line stok = qty × harga jual = ${formatNumber(minimumAmount)}.`
                : 'Isi produk dan qty untuk menghitung total minimum line stok.')
            : 'Untuk service_fee dan outside_cost, amount diisi manual.';

        return `
            <div class="card mb-3 transaction-line-card" data-index="${index}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Line ${index + 1}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" ${state.lines.length === 1 ? 'disabled' : ''}>
                        Hapus
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="lines[${index}][kind]" class="form-select line-kind" required>
                            <option value="product_sale" ${line.kind === 'product_sale' ? 'selected' : ''}>product_sale</option>
                            <option value="service_fee" ${line.kind === 'service_fee' ? 'selected' : ''}>service_fee</option>
                            <option value="service_product" ${line.kind === 'service_product' ? 'selected' : ''}>service_product</option>
                            <option value="outside_cost" ${line.kind === 'outside_cost' ? 'selected' : ''}>outside_cost</option>
                        </select>
                        <div class="form-text">
                            product_sale dan service_product memakai stok produk. service_fee dan outside_cost tetap manual.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <select name="lines[${index}][product_id]" class="form-select line-product-id">
                            ${buildProductOptions(line.product_id)}
                        </select>
                        <div class="form-text line-product-price-hint">${escapeHtml(productHint)}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Qty</label>
                        <input type="number" name="lines[${index}][qty]" min="1" class="form-control line-qty" value="${escapeHtml(line.qty ?? '')}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount (total line)</label>
                        <input type="number" name="lines[${index}][amount]" min="0" class="form-control line-amount" value="${escapeHtml(line.amount ?? 0)}" required>
                        <div class="form-text line-amount-hint">${escapeHtml(amountHint)}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan Line</label>
                        <textarea name="lines[${index}][note]" class="form-control line-note">${escapeHtml(line.note ?? '')}</textarea>
                    </div>
                </div>
            </div>
        `;
    }

    function bindLineEvents() {
        root.querySelectorAll('.transaction-line-card').forEach((card) => {
            const index = parseInt(card.dataset.index, 10);

            const kindEl = card.querySelector('.line-kind');
            const productEl = card.querySelector('.line-product-id');
            const qtyEl = card.querySelector('.line-qty');
            const amountEl = card.querySelector('.line-amount');
            const noteEl = card.querySelector('.line-note');
            const removeBtn = card.querySelector('.remove-line-btn');

            kindEl.addEventListener('change', function () {
                state.lines[index].kind = kindEl.value;
                state.lines[index]._amountTouched = false;
                maybeAutofillAmount(index, true);
                render();
            });

            productEl.addEventListener('change', function () {
                state.lines[index].product_id = productEl.value;
                state.lines[index]._amountTouched = false;
                maybeAutofillAmount(index, true);
                render();
            });

            qtyEl.addEventListener('input', function () {
                state.lines[index].qty = qtyEl.value;
                state.lines[index]._amountTouched = false;
                maybeAutofillAmount(index, true);
                render();
            });

            amountEl.addEventListener('input', function () {
                state.lines[index].amount = amountEl.value;
                state.lines[index]._amountTouched = true;
                updateSingleHints(index);
            });

            noteEl.addEventListener('input', function () {
                state.lines[index].note = noteEl.value;
            });

            removeBtn.addEventListener('click', function () {
                if (state.lines.length === 1) {
                    return;
                }

                state.lines.splice(index, 1);
                render();
            });
        });
    }

    function maybeAutofillAmount(index, force) {
        const line = state.lines[index];
        const stockMode = usesStock(line.kind);

        if (!stockMode) {
            return;
        }

        const product = productById(line.product_id);
        const salePrice = product ? Number(product.sale_price) : 0;
        const qty = parseInt(line.qty || '0', 10) > 0 ? parseInt(line.qty || '0', 10) : 0;
        const computed = salePrice * qty;

        if (computed > 0 && (!line._amountTouched || force)) {
            line.amount = computed;
        }
    }

    function refreshAllHints() {
        state.lines.forEach((_, index) => updateSingleHints(index));
    }

    function updateSingleHints(index) {
        const card = root.querySelector(`.transaction-line-card[data-index="${index}"]`);
        if (!card) {
            return;
        }

        const line = state.lines[index];
        const stockMode = usesStock(line.kind);
        const product = productById(line.product_id);
        const salePrice = product ? Number(product.sale_price) : 0;
        const qty = parseInt(line.qty || '0', 10) > 0 ? parseInt(line.qty || '0', 10) : 0;
        const minimumAmount = stockMode ? salePrice * qty : 0;

        const priceHintEl = card.querySelector('.line-product-price-hint');
        const amountHintEl = card.querySelector('.line-amount-hint');

        if (stockMode) {
            priceHintEl.textContent = salePrice > 0
                ? `Harga jual master: ${formatNumber(salePrice)}.`
                : 'Pilih produk untuk mengambil harga jual master.';
            amountHintEl.textContent = minimumAmount > 0
                ? `Total minimum line stok = qty × harga jual = ${formatNumber(minimumAmount)}.`
                : 'Isi produk dan qty untuk menghitung total minimum line stok.';
        } else {
            priceHintEl.textContent = 'Line non-stok tidak memakai harga produk.';
            amountHintEl.textContent = 'Untuk service_fee dan outside_cost, amount diisi manual.';
        }
    }

    const state = {
        lines: (Array.isArray(initialLines) && initialLines.length > 0 ? initialLines : [defaultLine()])
            .map((line) => ({
                kind: line.kind ?? 'product_sale',
                product_id: line.product_id ?? '',
                qty: line.qty ?? 1,
                amount: line.amount ?? 0,
                note: line.note ?? '',
                _amountTouched: true,
            })),
    };

    addLineBtn.addEventListener('click', function () {
        state.lines.push({
            ...defaultLine(),
            _amountTouched: false,
        });
        maybeAutofillAmount(state.lines.length - 1, true);
        render();
    });

    state.lines.forEach((_, index) => maybeAutofillAmount(index, false));
    render();
});
</script>
@endsection
