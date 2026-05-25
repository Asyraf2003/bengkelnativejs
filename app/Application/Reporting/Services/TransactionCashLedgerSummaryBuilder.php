<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

final class TransactionCashLedgerSummaryBuilder
{
    public function build(array $rows): array
    {
        $totalIn = 0;
        $cashIn = 0;
        $transferIn = 0;
        $totalOut = 0;

        foreach ($rows as $row) {
            $amount = (int) ($row['event_amount_rupiah'] ?? 0);

            if (($row['direction'] ?? null) === 'in') {
                $totalIn += $amount;

                if (($row['payment_method'] ?? null) === 'cash') {
                    $cashIn += $amount;
                }

                if (($row['payment_method'] ?? null) === 'transfer') {
                    $transferIn += $amount;
                }

                continue;
            }

            if (($row['direction'] ?? null) === 'out') {
                $totalOut += $amount;
            }
        }

        return [
            'total_events' => count($rows),
            'total_cash_in_rupiah' => $totalIn,
            'cash_in_rupiah' => $cashIn,
            'transfer_in_rupiah' => $transferIn,
            'total_cash_out_rupiah' => $totalOut,
            'net_amount_rupiah' => $totalIn - $totalOut,
        ];
    }
}
