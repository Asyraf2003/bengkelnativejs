@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Transaksi</h1>

    @if (session('status'))
        <div class="alert alert-success mb-3">{{ session('status') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.transactions.create') }}" class="btn btn-primary">Buat Draft</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Lines</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->transacted_at?->toDateString() }}</td>
                                <td>{{ $row->customer_name }}</td>
                                <td>{{ $row->status }}</td>
                                <td>{{ $row->lines_count }}</td>
                                <td class="d-flex gap-2 flex-wrap">
                                    @if ($row->status === 'draft')
                                        <form method="POST" action="{{ route('admin.transactions.mark_paid', $row) }}">
                                            @csrf
                                            <input type="hidden" name="paid_at" value="{{ now()->toDateString() }}">
                                            <button type="submit" class="btn btn-sm btn-success">Mark Paid</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.transactions.cancel', $row) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                        </form>
                                    @endif

                                    @if ($row->status === 'paid' && (int) $row->refundable_stock_lines_count > 0)
                                        <a href="{{ route('admin.transactions.refund', $row) }}" class="btn btn-sm btn-warning">Refund</a>
                                    @endif
                                </td>
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
