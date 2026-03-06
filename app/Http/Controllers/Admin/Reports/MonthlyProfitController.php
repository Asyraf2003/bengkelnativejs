<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Application\UseCases\Reports\BuildMonthlyProfitReportUseCase;
use App\Http\Requests\Admin\Reports\MonthlyProfitRequest;

class MonthlyProfitController
{
    public function __invoke(
        MonthlyProfitRequest $request,
        BuildMonthlyProfitReportUseCase $useCase
    ) {
        $data = $request->validated();

        $month = isset($data['month']) && $data['month'] !== ''
            ? (int) $data['month']
            : (int) now()->month;

        $year = isset($data['year']) && $data['year'] !== ''
            ? (int) $data['year']
            : (int) now()->year;

        $report = $useCase->execute([
            'month' => $month,
            'year' => $year,
        ]);

        return view('admin.reports.monthly_profit', compact('month', 'year', 'report'));
    }
}
