<?php

namespace App\Application\UseCases\Reports;

use Carbon\CarbonImmutable;

class BuildMonthlyProfitReportUseCase
{
    public function __construct(
        private readonly BuildDailyProfitReportUseCase $dailyReportUseCase
    ) {
    }

    /**
     * @param array{month:int, year:int} $input
     * @return array{
     *   month:int,
     *   year:int,
     *   days: array<int, array{
     *     date:string,
     *     cash_in:int,
     *     cash_out:int,
     *     cogs:int,
     *     profit:int
     *   }>,
     *   totals: array{
     *     cash_in:int,
     *     cash_out:int,
     *     cogs:int,
     *     profit:int
     *   }
     * }
     */
    public function execute(array $input): array
    {
        $month = (int) $input['month'];
        $year = (int) $input['year'];

        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $end = $start->endOfMonth();

        $days = [];

        $totalCashIn = 0;
        $totalCashOut = 0;
        $totalCogs = 0;
        $totalProfit = 0;

        for ($cursor = $start; $cursor->lte($end); $cursor = $cursor->addDay()) {
            $daily = $this->dailyReportUseCase->execute([
                'date' => $cursor->toDateString(),
            ]);

            $cashIn = (int) $daily['cash_in']['total'];
            $cashOut = (int) $daily['cash_out']['total'];
            $cogs = (int) $daily['cogs'];
            $profit = (int) $daily['profit'];

            $days[] = [
                'date' => $cursor->toDateString(),
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'cogs' => $cogs,
                'profit' => $profit,
            ];

            $totalCashIn += $cashIn;
            $totalCashOut += $cashOut;
            $totalCogs += $cogs;
            $totalProfit += $profit;
        }

        return [
            'month' => $month,
            'year' => $year,
            'days' => $days,
            'totals' => [
                'cash_in' => $totalCashIn,
                'cash_out' => $totalCashOut,
                'cogs' => $totalCogs,
                'profit' => $totalProfit,
            ],
        ];
    }
}
