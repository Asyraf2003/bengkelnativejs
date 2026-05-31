<template id="workspace-template-service_store_stock">
    <div class="border rounded px-3 py-2 mb-2" data-line-item data-item-type="service_store_stock">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
            <div>
                <h6 class="mb-0 small fw-semibold" data-line-title>Rincian</h6>
                <small class="text-muted">Servis + sparepart toko, total paket auto split.</small>
            </div>
            <button type="button" class="btn btn-sm btn-light-danger py-1" data-remove-line>Hapus</button>
        </div>

        <input type="hidden" name="items[__INDEX__][entry_mode]" value="service">
        <input type="hidden" name="items[__INDEX__][part_source]" value="store_stock">
        <input type="hidden" name="items[__INDEX__][pay_now]" value="0" data-pay-now>
        <input type="hidden" name="items[__INDEX__][pricing_mode]" value="package_auto_split" data-pricing-mode>
        <input type="hidden" name="items[__INDEX__][service][price_rupiah]" value="0" data-money-raw>
        <input type="hidden" name="items[__INDEX__][service][notes]" value="">

        <div class="row g-2 align-items-start mb-2">
            <div class="col-12 col-lg-6">
                <label class="form-label small mb-1">Nama Servis</label>
                <input
                    type="text"
                    name="items[__INDEX__][service][name]"
                    value=""
                    class="form-control form-control-sm"
                    placeholder="Ganti Kampas Rem"
                >
            </div>

            <div class="col-12 col-lg-3">
                <div data-money-input-group>
                    <label class="form-label small mb-1">Total Paket</label>
                    <input type="hidden" name="items[__INDEX__][package_total_rupiah]" value="" data-money-raw>
                    <input
                        type="text"
                        inputmode="numeric"
                        value=""
                        class="form-control form-control-sm"
                        placeholder="300.000"
                        data-money-display
                        data-package-total-input
                    >
                </div>
            </div>
        </div>

        <div class="vstack gap-2" data-product-lines>
            <div class="row g-2 align-items-start" data-product-line>
                <div class="col-12 col-lg-7">
                    <label class="form-label small mb-1">Sparepart Toko</label>
                    <div class="position-relative">
                        <input type="hidden" name="items[__INDEX__][product_lines][0][product_id]" value="" data-product-id>
                        <input type="hidden" name="items[__INDEX__][product_lines][0][price_basis]" value="current_catalog" data-price-basis>
                        <input type="hidden" name="items[__INDEX__][product_lines][0][unit_price_rupiah]" value="" data-money-raw data-price-input>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Pilih produk dari pencarian"
                            autocomplete="off"
                            data-product-search
                        >
                        <div class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 20;" data-product-results></div>
                    </div>
                    <small class="text-muted">Wajib pilih dari hasil pencarian, bukan diketik manual.</small>
                </div>

                <div class="col-auto">
                    <label class="form-label small mb-1">Qty</label>
                    <input
                        type="text"
                        inputmode="numeric"
                        name="items[__INDEX__][product_lines][0][qty]"
                        value="1"
                        class="form-control form-control-sm text-center px-1 fw-semibold"
                        style="width: 3rem;"
                        data-qty-input
                    >
                </div>

                <div class="col-4 col-lg-2 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-light-danger w-100 mt-4 d-none" data-remove-product-line>Hapus</button>
                </div>

                <div class="col-12">
                    <small class="text-muted me-3" data-stock-text>Stok tersedia: -</small>
                    <small class="text-muted me-3" data-min-price-text>Harga produk mengikuti katalog.</small>
                    <small class="text-danger d-none" data-stock-error>Qty melebihi stok tersedia.</small>
                    <small class="text-danger d-none" data-min-price-warning>Harga tidak boleh di bawah minimum.</small>
                </div>
            </div>
        </div>

        <template data-product-line-template>
            <div class="row g-2 align-items-start" data-product-line>
                <div class="col-12 col-lg-7">
                    <label class="form-label small mb-1">Sparepart Toko</label>
                    <div class="position-relative">
                        <input type="hidden" name="items[__INDEX__][product_lines][__PRODUCT_INDEX__][product_id]" value="" data-product-id>
                        <input type="hidden" name="items[__INDEX__][product_lines][__PRODUCT_INDEX__][price_basis]" value="current_catalog" data-price-basis>
                        <input type="hidden" name="items[__INDEX__][product_lines][__PRODUCT_INDEX__][unit_price_rupiah]" value="" data-money-raw data-price-input>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Pilih produk dari pencarian"
                            autocomplete="off"
                            data-product-search
                        >
                        <div class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 20;" data-product-results></div>
                    </div>
                    <small class="text-muted">Wajib pilih dari hasil pencarian, bukan diketik manual.</small>
                </div>

                <div class="col-auto">
                    <label class="form-label small mb-1">Qty</label>
                    <input
                        type="text"
                        inputmode="numeric"
                        name="items[__INDEX__][product_lines][__PRODUCT_INDEX__][qty]"
                        value="1"
                        class="form-control form-control-sm text-center px-1 fw-semibold"
                        style="width: 3rem;"
                        data-qty-input
                    >
                </div>

                <div class="col-4 col-lg-2 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-light-danger w-100 mt-4" data-remove-product-line>Hapus</button>
                </div>

                <div class="col-12">
                    <small class="text-muted me-3" data-stock-text>Stok tersedia: -</small>
                    <small class="text-muted me-3" data-min-price-text>Harga produk mengikuti katalog.</small>
                    <small class="text-danger d-none" data-stock-error>Qty melebihi stok tersedia.</small>
                    <small class="text-danger d-none" data-min-price-warning>Harga tidak boleh di bawah minimum.</small>
                </div>
            </div>
        </template>

        <button type="button" class="btn btn-sm btn-light-primary mt-2" data-add-product-line>
            Tambah sparepart
        </button>
    </div>
</template>
