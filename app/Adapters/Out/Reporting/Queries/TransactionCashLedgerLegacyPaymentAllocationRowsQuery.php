<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TransactionCashLedgerLegacyPaymentAllocationRowsQuery
{
    public function query(string $fromEventDate, string $toEventDate): Builder
    {
        return DB::table('payment_allocations')
            ->join('customer_payments', 'customer_payments.id', '=', 'payment_allocations.customer_payment_id')
            ->leftJoin('notes', 'notes.id', '=', 'payment_allocations.note_id')
            ->whereBetween('customer_payments.paid_at', [$fromEventDate, $toEventDate])
            ->groupBy(
                'payment_allocations.note_id',
                'notes.customer_name',
                'notes.transaction_date',
                'customer_payments.paid_at',
                'customer_payments.payment_method',
                'payment_allocations.customer_payment_id'
            )
            ->select([
                'payment_allocations.note_id',
                'notes.customer_name',
                'notes.transaction_date',
                DB::raw('customer_payments.paid_at as event_date'),
                DB::raw('SUM(payment_allocations.amount_rupiah) as event_amount_rupiah'),
                'payment_allocations.customer_payment_id',
                DB::raw("COALESCE(NULLIF(customer_payments.payment_method, ''), 'unknown') as payment_method"),
                DB::raw("'payment_allocations' as source_table"),
            ]);
    }
}
