@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Faktur Supplier</h1>

    @if (session('status'))
        <div class="alert alert-success mb-3">{{ session('status') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary">Buat Faktur</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Invoice No</th>
                            <th>Supplier</th>
                            <th>Delivered</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th class="text-end">Grand Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->invoice_no }}</td>
                                <td>{{ $row->supplier_name }}</td>
                                <td>{{ $row->delivered_at?->toDateString() }}</td>
                                <td>{{ $row->due_at?->toDateString() }}</td>
                                <td>{{ $row->is_paid ? 'Paid' : 'Unpaid' }}</td>
                                <td class="text-end">{{ number_format($row->grand_total, 0, ',', '.') }}</td>
                                <td class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('admin.invoices.show', $row) }}" class="btn btn-sm btn-outline-primary">
                                        Detail
                                    </a>

                                    <a href="{{ route('admin.invoices.proofs.index', $row) }}" class="btn btn-sm btn-secondary">
                                        Proofs
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Belum ada data.</td>
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
