@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Report Profit Harian</h1>

    @if (session('status'))
        <div class="alert alert-success mb-3">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.reports.daily_profit') }}" class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="date" class="form-label">Tanggal</label>
                    <input
                        type="date"
                        id="date"
                        name="date"
                        class="form-control"
                        value="{{ $date }}"
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
            <strong>Tanggal:</strong> {{ $report['date'] }}
        </div>
        <div class="card-body">
            <h5 class="mb-3">Cash In</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>Revenue transaksi paid</th>
                            <td class="text-end">{{ number_format($report['cash_in']['transaction_revenue'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Pembayaran hutang karyawan</th>
                            <td class="text-end">{{ number_format($report['cash_in']['employee_loan_payments'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Total Cash In</th>
                            <td class="text-end"><strong>{{ number_format($report['cash_in']['total'], 0, ',', '.') }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h5 class="mb-3">Cash Out</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>Refund</th>
                            <td class="text-end">{{ number_format($report['cash_out']['refunds'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Operasional</th>
                            <td class="text-end">{{ number_format($report['cash_out']['operational_expenses'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Gaji</th>
                            <td class="text-end">{{ number_format($report['cash_out']['salaries'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Pinjaman karyawan</th>
                            <td class="text-end">{{ number_format($report['cash_out']['employee_loans'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Outside Cost</th>
                            <td class="text-end">{{ number_format($report['cash_out']['outside_cost'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Total Cash Out</th>
                            <td class="text-end"><strong>{{ number_format($report['cash_out']['total'], 0, ',', '.') }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h5 class="mb-3">COGS & Profit</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>COGS</th>
                            <td class="text-end">{{ number_format($report['cogs'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Profit</th>
                            <td class="text-end"><strong>{{ number_format($report['profit'], 0, ',', '.') }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
