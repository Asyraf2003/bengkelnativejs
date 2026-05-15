<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

use App\Support\ViewDateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionCashLedgerExcelDetailSheetWriter
{
    public function __construct(
        private readonly TransactionReportExcelTableWriter $tables,
    ) {}

    public function write(Worksheet $sheet, array $rows): void
    {
        $sheet->setTitle('Detail Event Kas');
        $values = [];

        foreach (array_values($rows) as $index => $row) {
            $values[] = [
                $index + 1,
                ViewDateFormatter::display($row['event_date'] ?? null),
                (string) ($row['note_id'] ?? ''),
                (string) ($row['note_label'] ?? ''),
                $this->eventTypeLabel((string) ($row['event_type'] ?? '')),
                $this->directionLabel((string) ($row['direction'] ?? '')),
                (int) ($row['event_amount_rupiah'] ?? 0),
                (string) ($row['customer_payment_id'] ?? ''),
                (string) ($row['refund_id'] ?? ''),
                (string) ($row['source_table'] ?? ''),
                (string) ($row['source_id'] ?? ''),
                (string) ($row['source_disposition_id'] ?? ''),
            ];
        }

        $this->tables->writeTable($sheet, 1, [
            'No',
            'Tanggal Event',
            'ID Nota',
            'Nota',
            'Jenis Kejadian',
            'Arah',
            'Nominal',
            'ID Pembayaran',
            'ID Pengembalian Dana',
            'Tabel Sumber',
            'ID Sumber',
            'ID Disposisi Sumber',
        ], $values);

        $this->tables->autosize($sheet, 12);
    }

    private function eventTypeLabel(string $type): string
    {
        return match ($type) {
            'payment_allocation' => 'Alokasi Pembayaran',
            'payment' => 'Pembayaran',
            'refund' => 'Pengembalian Dana',
            default => $type,
        };
    }

    private function directionLabel(string $direction): string
    {
        return match ($direction) {
            'in' => 'Masuk',
            'out' => 'Keluar',
            default => $direction,
        };
    }
}
