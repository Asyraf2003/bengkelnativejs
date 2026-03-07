<?php

namespace App\Application\UseCases\Reports;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildStockReportUseCase
{
    /**
     * @param array{per_page?:int} $input
     */
    public function execute(array $input = []): LengthAwarePaginator
    {
        $perPage = isset($input['per_page']) ? (int) $input['per_page'] : 20;

        if ($perPage < 1) {
            $perPage = 20;
        }

        return Product::query()
            ->with('inventory')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
