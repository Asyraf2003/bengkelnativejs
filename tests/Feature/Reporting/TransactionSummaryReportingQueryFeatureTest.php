<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Reporting\Queries\TransactionSummaryReportingQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionSummaryReportingQueryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reads_summary_from_cash_records(): void
    {
        $this->seedNote('note-1', 'Budi', '2026-04-02', 26000);
        $this->seedNote('note-2', 'Sari', '2026-04-03', 10000);

        $this->seedWorkItem('wi-1', 'note-1', 1, 5000);
        $this->seedWorkItem('wi-2', 'note-1', 2, 3000);
        $this->seedWorkItem('wi-3', 'note-2', 1, 10000);

        $this->seedCustomerPayment('pay-1', 8000, '2026-04-02');
        $this->seedCustomerPayment('pay-2', 4000, '2026-04-03');

        DB::table('payment_allocations')->insert([
            [
                'id' => 'pa-1',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'amount_rupiah' => 8000,
            ],
            [
                'id' => 'pa-2',
                'customer_payment_id' => 'pay-2',
                'note_id' => 'note-2',
                'amount_rupiah' => 4000,
            ],
        ]);

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'p1',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 5000,
                'allocated_amount_rupiah' => 5000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'p2',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-2',
                'component_type' => 'service_store_stock_part',
                'component_ref_id' => 'sto-2',
                'component_amount_rupiah_snapshot' => 3000,
                'allocated_amount_rupiah' => 3000,
                'allocation_priority' => 2,
            ],
            [
                'id' => 'p3',
                'customer_payment_id' => 'pay-2',
                'note_id' => 'note-2',
                'work_item_id' => 'wi-3',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-3',
                'component_amount_rupiah_snapshot' => 10000,
                'allocated_amount_rupiah' => 4000,
                'allocation_priority' => 1,
            ],
        ]);

        $this->seedCustomerRefund('ref-1', 'pay-1', 'note-1', 1000, '2026-04-04', 'Refund');

        DB::table('refund_component_allocations')->insert([
            [
                'id' => 'r1',
                'customer_refund_id' => 'ref-1',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-2',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-2',
                'refunded_amount_rupiah' => 1000,
                'refund_priority' => 1,
            ],
        ]);

        $query = app(TransactionSummaryReportingQuery::class);
        $rows = $query->rows('2026-04-01', '2026-04-30');
        $recon = $query->reconciliation('2026-04-01', '2026-04-30');

        $this->assertCount(2, $rows);
        $this->assertSame(8000, $rows[0]['allocated_payment_rupiah']);
        $this->assertSame(1000, $rows[0]['refunded_rupiah']);
        $this->assertSame(4000, $rows[1]['allocated_payment_rupiah']);
        $this->assertSame(0, $rows[1]['refunded_rupiah']);
        $this->assertSame(2, $recon['total_notes']);
        $this->assertSame(36000, $recon['gross_transaction_rupiah']);
        $this->assertSame(12000, $recon['allocated_payment_rupiah']);
        $this->assertSame(1000, $recon['refunded_rupiah']);
    }

    public function test_transaction_summary_uses_cash_records_when_component_allocations_are_rebuilt_after_refund_revision(): void
    {
        $this->seedNote('note-revision-summary', 'Budi Revision', '2026-04-30', 0);
        $this->seedWorkItem('wi-old-refunded', 'note-revision-summary', 1, 122000);
        $this->seedWorkItem('wi-current', 'note-revision-summary', 2, 143000);

        $this->seedCustomerPayment('pay-revision', 265000, '2026-04-30');

        DB::table('payment_allocations')->insert([
            'id' => 'pa-revision',
            'customer_payment_id' => 'pay-revision',
            'note_id' => 'note-revision-summary',
            'amount_rupiah' => 265000,
        ]);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-current-after-rebuild',
            'customer_payment_id' => 'pay-revision',
            'note_id' => 'note-revision-summary',
            'work_item_id' => 'wi-current',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-current',
            'component_amount_rupiah_snapshot' => 143000,
            'allocated_amount_rupiah' => 143000,
            'allocation_priority' => 1,
        ]);

        $this->seedCustomerRefund('refund-old-line', 'pay-revision', 'note-revision-summary', 122000, '2026-04-30', 'Refund old component');
        $this->seedCustomerRefund('refund-current-line', 'pay-revision', 'note-revision-summary', 143000, '2026-04-30', 'Refund current component');

        DB::table('refund_component_allocations')->insert([
            [
                'id' => 'rca-old-line',
                'customer_refund_id' => 'refund-old-line',
                'customer_payment_id' => 'pay-revision',
                'note_id' => 'note-revision-summary',
                'work_item_id' => 'wi-old-refunded',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-old-refunded',
                'refunded_amount_rupiah' => 122000,
                'refund_priority' => 1,
            ],
            [
                'id' => 'rca-current-line',
                'customer_refund_id' => 'refund-current-line',
                'customer_payment_id' => 'pay-revision',
                'note_id' => 'note-revision-summary',
                'work_item_id' => 'wi-current',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-current',
                'refunded_amount_rupiah' => 143000,
                'refund_priority' => 1,
            ],
        ]);

        $query = app(TransactionSummaryReportingQuery::class);
        $rows = $query->rows('2026-04-01', '2026-04-30');

        $this->assertCount(1, $rows);
        $this->assertSame(265000, $rows[0]['allocated_payment_rupiah']);
        $this->assertSame(265000, $rows[0]['refunded_rupiah']);
    }


    public function test_downward_revision_surplus_reporting_uses_capped_allocations_not_customer_payment_gross(): void
    {
        $this->seedNote('note-downward-surplus-report', 'Budi Surplus', '2026-04-30', 143000);
        $this->seedWorkItem('wi-current-surplus-report', 'note-downward-surplus-report', 1, 143000);

        $this->seedCustomerPayment('pay-downward-surplus-report', 265000, '2026-04-30');

        DB::table('payment_allocations')->insert([
            'id' => 'pa-downward-surplus-report',
            'customer_payment_id' => 'pay-downward-surplus-report',
            'note_id' => 'note-downward-surplus-report',
            'amount_rupiah' => 143000,
        ]);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-downward-surplus-report',
            'customer_payment_id' => 'pay-downward-surplus-report',
            'note_id' => 'note-downward-surplus-report',
            'work_item_id' => 'wi-current-surplus-report',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-current-surplus-report',
            'component_amount_rupiah_snapshot' => 143000,
            'allocated_amount_rupiah' => 143000,
            'allocation_priority' => 1,
        ]);

        $query = app(TransactionSummaryReportingQuery::class);
        $rawRows = $query->rows('2026-04-01', '2026-04-30');
        $recon = $query->reconciliation('2026-04-01', '2026-04-30');

        $this->assertCount(1, $rawRows);
        $this->assertSame(143000, $rawRows[0]['gross_transaction_rupiah']);
        $this->assertSame(143000, $rawRows[0]['allocated_payment_rupiah']);
        $this->assertSame(0, $rawRows[0]['refunded_rupiah']);

        $builder = app(\App\Application\Reporting\Services\TransactionSummaryPerNoteBuilder::class);
        $rows = $builder->build($rawRows);
        $row = $rows[0]->toArray();

        $this->assertSame(143000, $row['gross_transaction_rupiah']);
        $this->assertSame(143000, $row['allocated_payment_rupiah']);
        $this->assertSame(143000, $row['net_cash_collected_rupiah']);
        $this->assertSame(0, $row['outstanding_rupiah']);
        $this->assertSame('Lunas', $row['payment_status_label']);

        $this->assertSame(1, $recon['total_notes']);
        $this->assertSame(143000, $recon['gross_transaction_rupiah']);
        $this->assertSame(143000, $recon['allocated_payment_rupiah']);
        $this->assertSame(0, $recon['refunded_rupiah']);
    }


    private function seedNote(string $id, string $customerName, string $transactionDate, int $totalRupiah): void
    {
        DB::table('notes')->insert([
            'id' => $id,
            'customer_name' => $customerName,
            'transaction_date' => $transactionDate,
            'total_rupiah' => $totalRupiah,
        ]);
    }

    private function seedWorkItem(string $id, string $noteId, int $lineNo, int $subtotalRupiah): void
    {
        DB::table('work_items')->insert([
            'id' => $id,
            'note_id' => $noteId,
            'line_no' => $lineNo,
            'transaction_type' => 'service_only',
            'status' => 'open',
            'subtotal_rupiah' => $subtotalRupiah,
        ]);
    }

    private function seedCustomerPayment(string $id, int $amountRupiah, string $paidAt): void
    {
        DB::table('customer_payments')->insert([
            'id' => $id,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
        ]);
    }

    private function seedCustomerRefund(
        string $id,
        string $paymentId,
        string $noteId,
        int $amountRupiah,
        string $refundedAt,
        string $reason
    ): void {
        DB::table('customer_refunds')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
            'refunded_at' => $refundedAt,
            'reason' => $reason,
        ]);
    }
}
