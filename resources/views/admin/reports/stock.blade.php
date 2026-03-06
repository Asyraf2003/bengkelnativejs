@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4">Report Stok</h1>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Brand</th>
                            <th>Size</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-end">On Hand</th>
                            <th class="text-end">Reserved</th>
                            <th class="text-end">Available</th>
                            <th class="text-end">Avg Cost</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row->code }}</td>
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->brand }}</td>
                                <td>{{ $row->size }}</td>
                                <td class="text-end">{{ number_format($row->sale_price, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int) ($row->inventory->on_hand_qty ?? 0), 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((int) ($row->inventory->reserved_qty ?? 0), 0, ',', '.') }}</td>
                                <td class="text-end">
                                    {{ number_format((int) (($row->inventory->on_hand_qty ?? 0) - ($row->inventory->reserved_qty ?? 0)), 0, ',', '.') }}
                                </td>
                                <td class="text-end">{{ number_format((int) ($row->inventory->avg_cost ?? 0), 0, ',', '.') }}</td>
                                <td>{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">Belum ada data.</td>
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
