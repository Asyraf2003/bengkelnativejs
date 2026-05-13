<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

final class TransactionCustomerBreakdownBuilder
{
    public function build(array $rows): array
    {
        $customers = [];

        foreach ($rows as $row) {
            $customerName = (string) ($row['customer_name'] ?? '');

            if ($customerName === '') {
                continue;
            }

            if (! isset($customers[$customerName])) {
                $customers[$customerName] = [
                    'customer_name' => $customerName,
                    'total_rows' => 0,
                    'gross_transaction_rupiah' => 0,
                    'allocated_payment_rupiah' => 0,
                    'refunded_rupiah' => 0,
                    'refund_due_rupiah' => 0,
                    'net_cash_collected_rupiah' => 0,
                    'outstanding_rupiah' => 0,
                ];
            }

            $customers[$customerName]['total_rows']++;
            $customers[$customerName]['gross_transaction_rupiah'] += (int) ($row['gross_transaction_rupiah'] ?? 0);
            $customers[$customerName]['allocated_payment_rupiah'] += (int) ($row['allocated_payment_rupiah'] ?? 0);
            $customers[$customerName]['refunded_rupiah'] += (int) ($row['refunded_rupiah'] ?? 0);
            $customers[$customerName]['refund_due_rupiah'] += (int) ($row['refund_due_rupiah'] ?? 0);
            $customers[$customerName]['net_cash_collected_rupiah'] += (int) ($row['net_cash_collected_rupiah'] ?? 0);
            $customers[$customerName]['outstanding_rupiah'] += (int) ($row['outstanding_rupiah'] ?? 0);
        }

        $customerRows = array_values($customers);

        usort($customerRows, static function (array $left, array $right): int {
            $byGross = $right['gross_transaction_rupiah'] <=> $left['gross_transaction_rupiah'];

            if ($byGross !== 0) {
                return $byGross;
            }

            return strcmp((string) $left['customer_name'], (string) $right['customer_name']);
        });

        return $customerRows;
    }
}
