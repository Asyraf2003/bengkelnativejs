<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Application\UseCases\Reports\BuildDailyProfitReportUseCase;
use App\Http\Requests\Admin\Reports\DailyProfitRequest;
use Carbon\CarbonImmutable;

class DailyProfitController
{
    public function __invoke(
        DailyProfitRequest $request,
        BuildDailyProfitReportUseCase $useCase
    ) {
        $data = $request->validated();

        $date = isset($data['date']) && $data['date'] !== ''
            ? CarbonImmutable::parse($data['date'])->toDateString()
            : now()->toDateString();

        $report = $useCase->execute([
            'date' => $date,
        ]);

        return view('admin.reports.daily_profit', compact('date', 'report'));
    }
}
