<template id="workspace-template-service_external">
    <div class="border rounded p-3 mb-3" data-line-item data-item-type="service_external">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h6 class="mb-0" data-line-title>Rincian</h6>
                <small class="text-muted">Servis dengan pembelian sparepart dari luar.</small>
            </div>
            <button type="button" class="btn btn-sm btn-light-danger" data-remove-line>Hapus</button>
        </div>

        <input type="hidden" name="items[__INDEX__][entry_mode]" value="service">
        <input type="hidden" name="items[__INDEX__][part_source]" value="external_purchase">
        <input type="hidden" name="items[__INDEX__][pay_now]" value="0" data-pay-now>

        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="form-group mb-3">
                    <label class="form-label">Nama Servis</label>
                    <input type="text" name="items[__INDEX__][service][name]" value="" class="form-control" placeholder="Contoh: Ganti Bearing">
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="form-group mb-3" data-money-input-group>
                    <label class="form-label">Harga Servis (Rupiah)</label>
                    <input type="hidden" name="items[__INDEX__][service][price_rupiah]" value="" data-money-raw>
                    <input type="text" inputmode="numeric" value="" class="form-control" placeholder="Contoh: 80.000" data-money-display>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="form-group mb-3">
                    <label class="form-label">Nama Part Luar</label>
                    <input type="text" name="items[__INDEX__][external_purchase_lines][0][label]" value="" class="form-control" placeholder="Contoh: Bearing NTN">
                </div>
            </div>

            <div class="col-auto">
                <div class="form-group mb-3">
                    <label class="form-label">Qty</label>
                    <input
                        type="text"
                        inputmode="numeric"
                        name="items[__INDEX__][external_purchase_lines][0][qty]"
                        value="1"
                        class="form-control form-control-sm text-center px-1 fw-semibold"
                        style="width: 3rem;"
                    >
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="form-group mb-3" data-money-input-group>
                    <label class="form-label">Biaya Satuan (Rupiah)</label>
                    <input type="hidden" name="items[__INDEX__][external_purchase_lines][0][unit_cost_rupiah]" value="" data-money-raw>
                    <input type="text" inputmode="numeric" value="" class="form-control" placeholder="Contoh: 120.000" data-money-display>
                </div>
            </div>

            <div class="col-12">
                <div class="form-group mb-0">
                    <label class="form-label">Catatan Servis</label>
                    <textarea name="items[__INDEX__][service][notes]" rows="2" class="form-control" placeholder="Catatan servis"></textarea>
                </div>
            </div>
        </div>
    </div>
</template>
