@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Report Profit Bulanan</h1>

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.reports.monthly_profit') }}" class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="month" class="form-label">Bulan</label>
                    <input
                        type="number"
                        id="month"
                        name="month"
                        min="1"
                        max="12"
                        class="form-control"
                        value="{{ $month }}"
                    >
                </div>
                <div class="col-md-3">
                    <label for="year" class="form-label">Tahun</label>
                    <input
                        type="number"
                        id="year"
                        name="year"
                        min="2000"
                        max="2100"
                        class="form-control"
                        value="{{ $year }}"
                    >
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-header">
            <strong>Periode:</strong> {{ str_pad((string) $report['month'], 2, '0', STR_PAD_LEFT) }}/{{ $report['year'] }}
        </div>
        <div class="card-body">
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="text-end">Cash In</th>
                            <th class="text-end">Cash Out</th>
                            <th class="text-end">COGS</th>
                            <th class="text-end">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['days'] as $day)
                            <tr>
                                <td>{{ $day['date'] }}</td>
                                <td class="text-end">{{ number_format($day['cash_in'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($day['cash_out'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($day['cogs'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($day['profit'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <th class="text-end">{{ number_format($report['totals']['cash_in'], 0, ',', '.') }}</th>
                            <th class="text-end">{{ number_format($report['totals']['cash_out'], 0, ',', '.') }}</th>
                            <th class="text-end">{{ number_format($report['totals']['cogs'], 0, ',', '.') }}</th>
                            <th class="text-end">{{ number_format($report['totals']['profit'], 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
