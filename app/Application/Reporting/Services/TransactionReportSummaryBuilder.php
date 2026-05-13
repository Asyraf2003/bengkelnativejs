<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

final class TransactionReportSummaryBuilder
{
    public function build(array $rows): array
    {
        $settledRows = 0;
        $outstandingRows = 0;

        foreach ($rows as $row) {
            $outstanding = (int) ($row['outstanding_rupiah'] ?? 0);

            if ($outstanding > 0) {
                $outstandingRows++;
                continue;
            }

            $settledRows++;
        }

        return [
            'total_rows' => count($rows),
            'gross_transaction_rupiah' => array_sum(array_column($rows, 'gross_transaction_rupiah')),
            'allocated_payment_rupiah' => array_sum(array_column($rows, 'allocated_payment_rupiah')),
            'refunded_rupiah' => array_sum(array_column($rows, 'refunded_rupiah')),
            'refund_due_rupiah' => array_sum(array_column($rows, 'refund_due_rupiah')),
            'net_cash_collected_rupiah' => array_sum(array_column($rows, 'net_cash_collected_rupiah')),
            'outstanding_rupiah' => array_sum(array_column($rows, 'outstanding_rupiah')),
            'settled_rows' => $settledRows,
            'outstanding_rows' => $outstandingRows,
        ];
    }
}
