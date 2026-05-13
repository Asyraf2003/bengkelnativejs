<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Facades\DB;

final class TransactionSummaryReportingQuery
{
    public function __construct(
        private readonly TransactionSummaryRefundDueTotalsQuery $refundDueTotals,
    ) {
    }

    public function rows(string $fromTransactionDate, string $toTransactionDate): array
    {
        $cashPaymentTotals = $this->cashPaymentTotals();
        $cashRefundTotals = DB::table('customer_refunds')
            ->selectRaw('note_id, SUM(amount_rupiah) as refunded_rupiah')
            ->groupBy('note_id');
        $refundDueTotals = $this->refundDueTotals->query();

        return DB::table('notes')
            ->leftJoinSub($cashPaymentTotals, 'cash_payment_totals', fn ($join) => $join->on('cash_payment_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($cashRefundTotals, 'cash_refund_totals', fn ($join) => $join->on('cash_refund_totals.note_id', '=', 'notes.id'))
            ->leftJoinSub($refundDueTotals, 'refund_due_totals', fn ($join) => $join->on('refund_due_totals.note_id', '=', 'notes.id'))
            ->whereBetween('notes.transaction_date', [$fromTransactionDate, $toTransactionDate])
            ->orderBy('notes.transaction_date')
            ->orderBy('notes.id')
            ->get([
                'notes.id as note_id',
                'notes.transaction_date',
                'notes.customer_name',
                'notes.total_rupiah as gross_transaction_rupiah',
                DB::raw('COALESCE(cash_payment_totals.allocated_payment_rupiah, 0) as allocated_payment_rupiah'),
                DB::raw('COALESCE(cash_refund_totals.refunded_rupiah, 0) as refunded_rupiah'),
                DB::raw('COALESCE(refund_due_totals.refund_due_rupiah, 0) as refund_due_rupiah'),
            ])
            ->map(static fn (object $row): array => [
                'note_id' => (string) $row->note_id,
                'transaction_date' => (string) $row->transaction_date,
                'customer_name' => (string) $row->customer_name,
                'gross_transaction_rupiah' => (int) $row->gross_transaction_rupiah,
                'allocated_payment_rupiah' => (int) $row->allocated_payment_rupiah,
                'refunded_rupiah' => (int) $row->refunded_rupiah,
                'refund_due_rupiah' => (int) $row->refund_due_rupiah,
            ])
            ->all();
    }

    public function reconciliation(string $fromTransactionDate, string $toTransactionDate): array
    {
        $rows = $this->rows($fromTransactionDate, $toTransactionDate);

        return [
            'total_notes' => count($rows),
            'gross_transaction_rupiah' => array_sum(array_column($rows, 'gross_transaction_rupiah')),
            'allocated_payment_rupiah' => array_sum(array_column($rows, 'allocated_payment_rupiah')),
            'refunded_rupiah' => array_sum(array_column($rows, 'refunded_rupiah')),
            'refund_due_rupiah' => array_sum(array_column($rows, 'refund_due_rupiah')),
        ];
    }

    private function cashPaymentTotals(): \Illuminate\Database\Query\Builder
    {
        $paymentAllocationRows = DB::table('payment_allocations')
            ->selectRaw('payment_allocations.note_id, SUM(payment_allocations.amount_rupiah) as amount_rupiah')
            ->groupBy('payment_allocations.note_id');

        $refundedPaymentFallbackRows = DB::table('customer_refunds')
            ->join('customer_payments', 'customer_payments.id', '=', 'customer_refunds.customer_payment_id')
            ->whereNotExists(static function ($query): void {
                $query->selectRaw('1')
                    ->from('payment_allocations')
                    ->whereColumn('payment_allocations.customer_payment_id', 'customer_refunds.customer_payment_id')
                    ->whereColumn('payment_allocations.note_id', 'customer_refunds.note_id');
            })
            ->selectRaw('customer_refunds.note_id, MAX(customer_payments.amount_rupiah) as amount_rupiah')
            ->groupBy('customer_refunds.note_id', 'customer_refunds.customer_payment_id');

        return DB::query()
            ->fromSub($paymentAllocationRows->unionAll($refundedPaymentFallbackRows), 'cash_payment_rows')
            ->selectRaw('note_id, SUM(amount_rupiah) as allocated_payment_rupiah')
            ->groupBy('note_id');
    }
}
