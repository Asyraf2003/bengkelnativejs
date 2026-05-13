@extends('layouts.app')
@include('layouts.partials.date-picker-assets')

@section('title', 'Laporan Transaksi')
@section('heading', 'Laporan Transaksi')

@section('content')
@include('admin.reporting.partials.period_filter', [
    'formId' => 'transaction-report-filter-form',
    'action' => route('admin.reports.transaction_summary.index'),
    'resetUrl' => route('admin.reports.transaction_summary.index'),
    'rangeLabelText' => 'Rentang transaksi aktif',
    'basisDateLabel' => 'Tanggal transaksi nota',
    'basisDateNote' => 'Mode harian hanya menghitung nota pada tanggal transaksi tersebut.',
    'exportActions' => [
        [
            'label' => 'Unduh Excel',
            'url' => $exportExcelUrl,
            'class' => 'btn btn-outline-success text-nowrap',
        ],
        [
            'label' => 'Unduh PDF',
            'url' => route('admin.reports.transaction_summary.export_pdf', request()->query()),
            'class' => 'btn btn-outline-danger text-nowrap',
        ],
    ],
])

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Jumlah Nota</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['total_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nilai Bruto Transaksi</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['gross_transaction_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Pembayaran Dialokasikan</div>
            <div class="fs-5 fw-bold text-success">Rp {{ number_format($summary['allocated_payment_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Dana Dikembalikan</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['refunded_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Kas Bersih</div>
            <div class="fs-5 fw-bold">Rp {{ number_format($summary['net_cash_collected_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Refund Due</div>
            <div class="fs-5 fw-bold text-warning">Rp {{ number_format($summary['refund_due_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6 col-xl-2">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Sisa Tagihan</div>
            <div class="fs-5 fw-bold text-danger">Rp {{ number_format($summary['outstanding_rupiah'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nota Lunas</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['settled_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card"><div class="card-body">
            <div class="text-muted small">Nota Sisa Tagihan</div>
            <div class="fs-5 fw-bold">{{ number_format($summary['outstanding_rows'] ?? 0, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Breakdown Per Tanggal</h5>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-end">Nota</th>
                                <th class="text-end">Gross</th>
                                <th class="text-end">Sisa Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periodRows as $row)
                                <tr>
                                    <td>{{ $row['period_label'] }}</td>
                                    <td class="text-end">{{ number_format($row['total_rows'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['gross_transaction_rupiah'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['outstanding_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada transaksi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Breakdown Customer</h5>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th class="text-end">Nota</th>
                                <th class="text-end">Gross</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customerRows as $row)
                                <tr>
                                    <td>{{ $row['customer_name'] }}</td>
                                    <td class="text-end">{{ number_format($row['total_rows'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['gross_transaction_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada customer pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    @include('layouts.partials.pagination', ['paginator' => $customerRows])
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Detail Per Nota</h5>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nota</th>
                                <th>Tanggal</th>
                                <th>Customer</th>
                                <th class="text-end">Gross</th>
                                <th class="text-end">Kas Bersih</th>
                                <th class="text-end">Refund Due</th>
                                <th class="text-end">Sisa Tagihan</th>
                            </tr>
                        </thead>
                        <tbody id="transaction-report-table-body">
                            @forelse ($rows as $row)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $row['customer_name'] }}</div>
                                        <div class="small text-muted">{{ \App\Support\ViewDateFormatter::display($row['transaction_date'] ?? null) }} · {{ $row['note_id'] }}</div>
                                    </td>
                                    <td>{{ \App\Support\ViewDateFormatter::display($row['transaction_date'] ?? null) }}</td>
                                    <td>{{ $row['customer_name'] }}</td>
                                    <td class="text-end">Rp {{ number_format($row['gross_transaction_rupiah'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['net_cash_collected_rupiah'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['refund_due_rupiah'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($row['outstanding_rupiah'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada transaksi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    @include('layouts.partials.pagination', ['paginator' => $rows])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
