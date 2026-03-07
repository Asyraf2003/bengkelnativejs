@extends('layouts.app')

@section('content')
@php
    $oldItems = old('items');

    if (!is_array($oldItems) || count($oldItems) < 1) {
        $oldItems = [[
            'product_id' => '',
            'qty' => 1,
            'total_cost' => 0,
        ]];
    }

    $productOptions = $products->map(fn ($product) => [
        'id' => (int) $product->id,
        'code' => (string) $product->code,
        'name' => (string) $product->name,
    ])->values()->all();
@endphp

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

        <div id="invoice-items-root"></div>

        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <strong>Grand Total</strong>
                <strong id="grand-total-text">0</strong>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="button" id="add-item-btn" class="btn btn-outline-secondary">Tambah Item</button>
            <button type="submit" class="btn btn-primary">Simpan Faktur</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($productOptions);
    const initialItems = @json($oldItems);

    const root = document.getElementById('invoice-items-root');
    const addItemBtn = document.getElementById('add-item-btn');
    const grandTotalText = document.getElementById('grand-total-text');

    function defaultItem() {
        return {
            product_id: '',
            qty: 1,
            total_cost: 0,
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

    function buildProductOptions(selectedId) {
        const selected = String(selectedId ?? '');

        let html = '<option value="">-- pilih produk --</option>';

        for (const product of products) {
            const isSelected = selected === String(product.id) ? 'selected' : '';
            html += `
                <option value="${product.id}" ${isSelected}>
                    ${escapeHtml(product.code)} - ${escapeHtml(product.name)}
                </option>
            `;
        }

        return html;
    }

    function buildItemCard(item, index) {
        const qty = parseInt(item.qty || '0', 10);
        const totalCost = parseInt(item.total_cost || '0', 10);
        const unitCost = qty > 0 ? Math.round(totalCost / qty) : 0;

        return `
            <div class="card mb-3 invoice-item-card" data-index="${index}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Item ${index + 1}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" ${state.items.length === 1 ? 'disabled' : ''}>
                        Hapus
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <select name="items[${index}][product_id]" class="form-select item-product-id" required>
                            ${buildProductOptions(item.product_id)}
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Qty</label>
                        <input type="number" name="items[${index}][qty]" min="1" class="form-control item-qty" value="${escapeHtml(item.qty ?? 1)}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Total Cost</label>
                        <input type="number" name="items[${index}][total_cost]" min="1" class="form-control item-total-cost" value="${escapeHtml(item.total_cost ?? 0)}" required>
                    </div>

                    <div class="form-text item-unit-cost-hint">
                        Estimasi unit cost: ${formatNumber(unitCost)}
                    </div>
                </div>
            </div>
        `;
    }

    function render() {
        root.innerHTML = state.items.map((item, index) => buildItemCard(item, index)).join('');
        bindEvents();
        refreshSummary();
    }

    function bindEvents() {
        root.querySelectorAll('.invoice-item-card').forEach((card) => {
            const index = parseInt(card.dataset.index, 10);

            const productEl = card.querySelector('.item-product-id');
            const qtyEl = card.querySelector('.item-qty');
            const totalCostEl = card.querySelector('.item-total-cost');
            const removeBtn = card.querySelector('.remove-item-btn');

            productEl.addEventListener('change', function () {
                state.items[index].product_id = productEl.value;
            });

            qtyEl.addEventListener('input', function () {
                state.items[index].qty = qtyEl.value;
                refreshSummary();
                updateUnitCostHint(index);
            });

            totalCostEl.addEventListener('input', function () {
                state.items[index].total_cost = totalCostEl.value;
                refreshSummary();
                updateUnitCostHint(index);
            });

            removeBtn.addEventListener('click', function () {
                if (state.items.length === 1) {
                    return;
                }

                state.items.splice(index, 1);
                render();
            });
        });
    }

    function updateUnitCostHint(index) {
        const card = root.querySelector(`.invoice-item-card[data-index="${index}"]`);
        if (!card) {
            return;
        }

        const item = state.items[index];
        const qty = parseInt(item.qty || '0', 10);
        const totalCost = parseInt(item.total_cost || '0', 10);
        const unitCost = qty > 0 ? Math.round(totalCost / qty) : 0;

        const hintEl = card.querySelector('.item-unit-cost-hint');
        hintEl.textContent = `Estimasi unit cost: ${formatNumber(unitCost)}`;
    }

    function refreshSummary() {
        const grandTotal = state.items.reduce((sum, item) => {
            return sum + (parseInt(item.total_cost || '0', 10) || 0);
        }, 0);

        grandTotalText.textContent = formatNumber(grandTotal);

        state.items.forEach((_, index) => updateUnitCostHint(index));
    }

    const state = {
        items: (Array.isArray(initialItems) && initialItems.length > 0 ? initialItems : [defaultItem()]).map((item) => ({
            product_id: item.product_id ?? '',
            qty: item.qty ?? 1,
            total_cost: item.total_cost ?? 0,
        })),
    };

    addItemBtn.addEventListener('click', function () {
        state.items.push(defaultItem());
        render();
    });

    render();
});
</script>
@endsection
