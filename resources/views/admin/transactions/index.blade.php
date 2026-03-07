@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Transaksi</h1>

    @if (session('status'))
        <div class="alert alert-success mb-3">{{ session('status') }}</div>
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

    <div class="mb-3">
        <a href="{{ route('admin.transactions.create') }}" class="btn btn-primary">Buat Draft</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
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
                                    <a href="{{ route('admin.transactions.show', $row) }}" class="btn btn-sm btn-outline-primary">Detail</a>

                                    @if ($row->status === 'draft')
                                        <form method="POST" action="{{ route('admin.transactions.mark_paid', $row) }}">
                                            @csrf
                                            <input type="hidden" name="paid_at" value="{{ now()->toDateString() }}">
                                            <button type="submit" class="btn btn-sm btn-success">Mark Paid</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.transactions.cancel', $row) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger">Cancel Draft</button>
                                        </form>
                                    @endif

                                    @if ($row->status === 'paid' && (int) $row->refundable_stock_lines_count > 0)
                                        <a href="{{ route('admin.transactions.refund', $row) }}" class="btn btn-sm btn-warning">Refund</a>
                                    @endif
                                </td>
                            </tr>

                            @if ($row->status === 'draft')
                                <tr>
                                    <td colspan="6">
                                        <div class="small text-muted mb-2">
                                            Line draft bisa dihapus satuan. Kalau semua dibatalkan, gunakan tombol <strong>Cancel Draft</strong>.
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Line ID</th>
                                                        <th>Jenis</th>
                                                        <th>Produk</th>
                                                        <th class="text-end">Qty</th>
                                                        <th class="text-end">Amount</th>
                                                        <th>Catatan</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($row->lines as $line)
                                                        <tr>
                                                            <td>{{ $line->id }}</td>
                                                            <td>{{ $line->kind }}</td>
                                                            <td>
                                                                @if ($line->product)
                                                                    {{ $line->product->code }} - {{ $line->product->name }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td class="text-end">{{ $line->qty ?? '-' }}</td>
                                                            <td class="text-end">{{ number_format((int) $line->amount, 0, ',', '.') }}</td>
                                                            <td>{{ $line->note }}</td>
                                                            <td>
                                                                @if ($row->lines->count() > 1)
                                                                    <form method="POST" action="{{ route('admin.transactions.lines.delete', [$row, $line]) }}">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                            Hapus Line
                                                                        </button>
                                                                    </form>
                                                                @else
                                                                    <span class="text-muted">Line terakhir</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="7" class="text-center">Tidak ada line.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
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
