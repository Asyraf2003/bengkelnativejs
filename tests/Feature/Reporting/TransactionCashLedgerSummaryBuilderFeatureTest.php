<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\Services\TransactionCashLedgerSummaryBuilder;
use Tests\TestCase;

final class TransactionCashLedgerSummaryBuilderFeatureTest extends TestCase
{
    public function test_summary_builder_splits_cash_and_transfer_money_in(): void
    {
        $summary = app(TransactionCashLedgerSummaryBuilder::class)->build([
            [
                'direction' => 'in',
                'event_amount_rupiah' => 85000,
                'payment_method' => 'cash',
            ],
            [
                'direction' => 'in',
                'event_amount_rupiah' => 30000,
                'payment_method' => 'transfer',
            ],
            [
                'direction' => 'out',
                'event_amount_rupiah' => 10000,
                'payment_method' => null,
            ],
        ]);

        $this->assertSame([
            'total_events' => 3,
            'total_cash_in_rupiah' => 115000,
            'cash_in_rupiah' => 85000,
            'transfer_in_rupiah' => 30000,
            'total_cash_out_rupiah' => 10000,
            'net_amount_rupiah' => 105000,
        ], $summary);
    }
}
