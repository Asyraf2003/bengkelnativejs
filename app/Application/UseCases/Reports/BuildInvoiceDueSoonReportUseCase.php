<?php

namespace App\Application\UseCases\Reports;

use App\Models\SupplierInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildInvoiceDueSoonReportUseCase
{
    /**
     * @param array{days?:int, per_page?:int} $input
     * @return array{
     *   today:string,
     *   until:string,
     *   rows: LengthAwarePaginator
     * }
     */
    public function execute(array $input = []): array
    {
        $days = isset($input['days']) ? (int) $input['days'] : 5;
        $perPage = isset($input['per_page']) ? (int) $input['per_page'] : 20;

        if ($days < 0) {
            $days = 5;
        }

        if ($perPage < 1) {
            $perPage = 20;
        }

        $today = CarbonImmutable::today()->toDateString();
        $until = CarbonImmutable::today()->addDays($days)->toDateString();

        $rows = SupplierInvoice::query()
            ->whereDate('due_at', '>=', $today)
            ->whereDate('due_at', '<=', $until)
            ->orderBy('due_at')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'today' => $today,
            'until' => $until,
            'rows' => $rows,
        ];
    }
}
