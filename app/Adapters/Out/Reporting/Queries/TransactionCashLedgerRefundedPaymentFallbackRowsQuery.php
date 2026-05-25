<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerRefundedPaymentFallbackRowsQuery
{
    public function query(string $fromEventDate, string $toEventDate): Builder
    {
        return DB::table('customer_refunds')
            ->join('customer_payments', 'customer_payments.id', '=', 'customer_refunds.customer_payment_id')
            ->leftJoin('notes', 'notes.id', '=', 'customer_refunds.note_id')
            ->whereBetween('customer_payments.paid_at', [$fromEventDate, $toEventDate])
            ->whereNotExists(static function ($query): void {
                $query->selectRaw('1')
                    ->from('payment_allocations')
                    ->whereColumn('payment_allocations.customer_payment_id', 'customer_refunds.customer_payment_id')
                    ->whereColumn('payment_allocations.note_id', 'customer_refunds.note_id');
            })
            ->whereNotExists(static function ($query): void {
                $query->selectRaw('1')
                    ->from('payment_component_allocations')
                    ->whereColumn('payment_component_allocations.customer_payment_id', 'customer_refunds.customer_payment_id')
                    ->whereColumn('payment_component_allocations.note_id', 'customer_refunds.note_id');
            })
            ->groupBy(
                'customer_refunds.note_id',
                'notes.customer_name',
                'notes.transaction_date',
                'customer_payments.paid_at',
                'customer_payments.payment_method',
                'customer_refunds.customer_payment_id'
            )
            ->select([
                DB::raw('customer_refunds.note_id as note_id'),
                'notes.customer_name',
                'notes.transaction_date',
                DB::raw('customer_payments.paid_at as event_date'),
                DB::raw('MAX(customer_payments.amount_rupiah) as event_amount_rupiah'),
                DB::raw('customer_refunds.customer_payment_id as customer_payment_id'),
                DB::raw("COALESCE(NULLIF(customer_payments.payment_method, ''), 'unknown') as payment_method"),
                DB::raw("'customer_payments' as source_table"),
            ]);
    }
}
