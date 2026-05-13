<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TransactionSummaryRefundDueTotalsQuery
{
    public function query(): Builder
    {
        return DB::table('note_revision_surplus_dispositions')
            ->selectRaw('note_root_id as note_id, SUM(amount_rupiah) as refund_due_rupiah')
            ->where('disposition_type', 'refund_due')
            ->where('status', 'active')
            ->groupBy('note_root_id');
    }
}
