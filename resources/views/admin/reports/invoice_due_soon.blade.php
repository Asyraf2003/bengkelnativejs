@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Report Faktur H-5</h1>

    <div class="card">
        <div class="card-header">
            <strong>Periode:</strong> {{ $today }} s/d {{ $until }}
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Supplier</th>
                            <th>Due At</th>
                            <th>Status</th>
                            <th>Paid At</th>
                            <th class="text-end">Grand Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row->invoice_no }}</td>
                                <td>{{ $row->supplier_name }}</td>
                                <td>{{ $row->due_at?->toDateString() }}</td>
                                <td>{{ $row->is_paid ? 'Paid' : 'Unpaid' }}</td>
                                <td>{{ $row->paid_at?->toDateString() }}</td>
                                <td class="text-end">{{ number_format($row->grand_total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
