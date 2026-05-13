<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

final class TransactionPeriodBreakdownBuilder
{
    public function build(array $rows): array
    {
        $periods = [];

        foreach ($rows as $row) {
            $date = (string) ($row['transaction_date'] ?? '');

            if ($date === '') {
                continue;
            }

            if (! isset($periods[$date])) {
                $periods[$date] = [
                    'period_label' => $date,
                    'total_rows' => 0,
                    'gross_transaction_rupiah' => 0,
                    'allocated_payment_rupiah' => 0,
                    'refunded_rupiah' => 0,
                    'refund_due_rupiah' => 0,
                    'net_cash_collected_rupiah' => 0,
                    'outstanding_rupiah' => 0,
                ];
            }

            $periods[$date]['total_rows']++;
            $periods[$date]['gross_transaction_rupiah'] += (int) ($row['gross_transaction_rupiah'] ?? 0);
            $periods[$date]['allocated_payment_rupiah'] += (int) ($row['allocated_payment_rupiah'] ?? 0);
            $periods[$date]['refunded_rupiah'] += (int) ($row['refunded_rupiah'] ?? 0);
            $periods[$date]['refund_due_rupiah'] += (int) ($row['refund_due_rupiah'] ?? 0);
            $periods[$date]['net_cash_collected_rupiah'] += (int) ($row['net_cash_collected_rupiah'] ?? 0);
            $periods[$date]['outstanding_rupiah'] += (int) ($row['outstanding_rupiah'] ?? 0);
        }

        ksort($periods);

        return array_values($periods);
    }
}
