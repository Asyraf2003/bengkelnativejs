<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerPaymentRowsQuery
{
    public function rows(string $fromEventDate, string $toEventDate): Collection
    {
        $paymentAllocationRows = DB::table('payment_allocations')
            ->join('customer_payments', 'customer_payments.id', '=', 'payment_allocations.customer_payment_id')
            ->leftJoin('notes', 'notes.id', '=', 'payment_allocations.note_id')
            ->whereBetween('customer_payments.paid_at', [$fromEventDate, $toEventDate])
            ->groupBy(
                'payment_allocations.note_id',
                'notes.customer_name',
                'notes.transaction_date',
                'customer_payments.paid_at',
                'payment_allocations.customer_payment_id'
            )
            ->select([
                'payment_allocations.note_id',
                'notes.customer_name',
                'notes.transaction_date',
                DB::raw('customer_payments.paid_at as event_date'),
                DB::raw('SUM(payment_allocations.amount_rupiah) as event_amount_rupiah'),
                'payment_allocations.customer_payment_id',
            ]);

        $refundedPaymentFallbackRows = DB::table('customer_refunds')
            ->join('customer_payments', 'customer_payments.id', '=', 'customer_refunds.customer_payment_id')
            ->leftJoin('notes', 'notes.id', '=', 'customer_refunds.note_id')
            ->whereBetween('customer_payments.paid_at', [$fromEventDate, $toEventDate])
            ->whereNotExists(static function ($query): void {
                $query->selectRaw('1')
                    ->from('payment_allocations')
                    ->whereColumn('payment_allocations.customer_payment_id', 'customer_refunds.customer_payment_id')
                    ->whereColumn('payment_allocations.note_id', 'customer_refunds.note_id');
            })
            ->groupBy(
                'customer_refunds.note_id',
                'notes.customer_name',
                'notes.transaction_date',
                'customer_payments.paid_at',
                'customer_refunds.customer_payment_id'
            )
            ->select([
                DB::raw('customer_refunds.note_id as note_id'),
                'notes.customer_name',
                'notes.transaction_date',
                DB::raw('customer_payments.paid_at as event_date'),
                DB::raw('MAX(customer_payments.amount_rupiah) as event_amount_rupiah'),
                DB::raw('customer_refunds.customer_payment_id as customer_payment_id'),
            ]);

        return DB::query()
            ->fromSub($paymentAllocationRows->unionAll($refundedPaymentFallbackRows), 'cash_payment_rows')
            ->orderBy('event_date')
            ->orderBy('customer_payment_id')
            ->get([
                'note_id',
                'customer_name',
                'transaction_date',
                'event_date',
                'event_amount_rupiah',
                'customer_payment_id',
            ])
            ->map(static fn (object $row): array => [
                'note_id' => (string) $row->note_id,
                'note_label' => trim((string) ($row->customer_name ?? '')) !== ''
                    ? (string) $row->customer_name . ' · ' . (string) ($row->transaction_date ?? $row->event_date)
                    : 'Nota ' . (string) ($row->transaction_date ?? $row->event_date),
                'event_date' => (string) $row->event_date,
                'event_type' => 'payment_allocation',
                'direction' => 'in',
                'event_amount_rupiah' => (int) $row->event_amount_rupiah,
                'customer_payment_id' => (string) $row->customer_payment_id,
                'refund_id' => null,
                'source_table' => 'customer_payments',
                'source_id' => (string) $row->customer_payment_id,
                'source_disposition_id' => null,
            ]);
    }
}
