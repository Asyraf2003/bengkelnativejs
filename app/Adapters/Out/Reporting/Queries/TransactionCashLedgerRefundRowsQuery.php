<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerRefundRowsQuery
{
    public function rows(string $fromEventDate, string $toEventDate): Collection
    {
        return DB::table('customer_refunds')
            ->leftJoin('notes', 'notes.id', '=', 'customer_refunds.note_id')
            ->whereBetween('customer_refunds.refunded_at', [$fromEventDate, $toEventDate])
            ->orderBy('customer_refunds.refunded_at')
            ->orderBy('customer_refunds.id')
            ->get([
                'customer_refunds.note_id',
                'notes.customer_name',
                'notes.transaction_date',
                'customer_refunds.refunded_at as event_date',
                'customer_refunds.amount_rupiah as event_amount_rupiah',
                'customer_refunds.customer_payment_id',
                'customer_refunds.id as refund_id',
            ])
            ->map(static fn (object $row): array => [
                'note_id' => (string) $row->note_id,
                'note_label' => trim((string) ($row->customer_name ?? '')) !== ''
                    ? (string) $row->customer_name . ' · ' . (string) ($row->transaction_date ?? $row->event_date)
                    : 'Nota ' . (string) ($row->transaction_date ?? $row->event_date),
                'event_date' => (string) $row->event_date,
                'event_type' => 'refund',
                'direction' => 'out',
                'event_amount_rupiah' => (int) $row->event_amount_rupiah,
                'customer_payment_id' => (string) $row->customer_payment_id,
                'refund_id' => (string) $row->refund_id,
                'source_table' => 'customer_refunds',
                'source_id' => (string) $row->refund_id,
                'source_disposition_id' => null,
            ]);
    }
}