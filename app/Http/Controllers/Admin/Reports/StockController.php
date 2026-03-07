<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Application\UseCases\Reports\BuildStockReportUseCase;

class StockController
{
    public function __invoke(BuildStockReportUseCase $useCase)
    {
        $rows = $useCase->execute([
            'per_page' => 20,
        ]);

        return view('admin.reports.stock', compact('rows'));
    }
}
