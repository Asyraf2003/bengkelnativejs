<div class="card">
  <div class="card-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h4 class="card-title mb-0">Header Nota</h4>
      </div>
      <span class="badge bg-light text-dark border">
        {{ count($note['rows']) }} Line
      </span>
    </div>
  </div>

  <div class="card-body">
    <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
      <small>No. Nota</small>
      <div class="text-end fw-semibold">{{ $note['customer_name'] ?? ($note['note_header']['customer_name'] ?? 'Nota Pelanggan') }}</div>
    </div>

    <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
      <small>Customer</small>
      <div class="text-end fw-semibold">{{ $note['customer_name'] }}</div>
    </div>

    <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
      <small>No. Telp</small>
      <div class="text-end fw-semibold">{{ !empty($note['customer_phone']) ? $note['customer_phone'] : '-' }}</div>
    </div>

    <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
      <small>Tanggal Nota</small>
      <div class="text-end fw-semibold">{{ \App\Support\ViewDateFormatter::display($note['transaction_date'] ?? null) }}</div>
    </div>

    @if (!empty($note['operational_note']))
      <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
        <small>Keterangan Nota</small>
        <div class="text-end fw-semibold">{{ $note['operational_note'] }}</div>
      </div>
    @endif

    <div class="ui-key-value d-flex justify-content-between align-items-start py-2 border-bottom">
      <small>Status Operasional</small>
      <div class="text-end">
        <span class="badge bg-light text-dark border text-uppercase">
          {{ $note['operational_status'] ?? $note['payment_status'] ?? '-' }}
        </span>
      </div>
    </div>

    <div class="ui-key-value d-flex justify-content-between align-items-start py-2">
      <small>Ringkasan Line</small>
      <div class="text-end fw-semibold">{{ $note['line_summary']['summary_label'] ?? 'Belum ada line.' }}</div>
    </div>
  </div>
</div>
