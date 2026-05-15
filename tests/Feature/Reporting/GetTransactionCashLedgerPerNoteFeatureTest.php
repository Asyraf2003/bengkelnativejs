<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetTransactionCashLedgerPerNoteHandler;
use App\Application\Shared\DTO\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetTransactionCashLedgerPerNoteFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_transaction_cash_ledger_per_note_handler_returns_cash_events_and_passes_reconciliation(): void
    {
        $this->seedNote('note-1', 'Budi', '2026-03-14', 100000);
        $this->seedNote('note-2', 'Siti', '2026-03-15', 50000);

        $this->seedCustomerPayment('payment-1', 80000, '2026-03-15');
        $this->seedCustomerPayment('payment-2', 50000, '2026-03-16');
        $this->seedCustomerPayment('payment-3', 70000, '2026-03-18');

        $this->seedPaymentAllocation('allocation-1', 'payment-1', 'note-1', 60000);
        $this->seedPaymentAllocation('allocation-2', 'payment-2', 'note-2', 50000);
        $this->seedPaymentAllocation('allocation-3', 'payment-3', 'note-1', 70000);

        $this->seedCustomerRefund('refund-1', 'payment-1', 'note-1', 10000, '2026-03-16', 'Koreksi');
        $this->seedCustomerRefund('refund-2', 'payment-3', 'note-1', 5000, '2026-03-18', 'Di luar scope');

        $result = app(GetTransactionCashLedgerPerNoteHandler::class)
            ->handle('2026-03-15', '2026-03-16');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $data = $result->data();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('rows', $data);

        $this->assertSame([
            [
                'note_id' => 'note-1',
                'event_date' => '2026-03-15',
                'event_type' => 'payment_allocation',
                'direction' => 'in',
                'event_amount_rupiah' => 60000,
                'customer_payment_id' => 'payment-1',
                'refund_id' => null,
                'source_table' => 'customer_payments',
                'source_id' => 'payment-1',
                'source_disposition_id' => null,
            ],
            [
                'note_id' => 'note-2',
                'event_date' => '2026-03-16',
                'event_type' => 'payment_allocation',
                'direction' => 'in',
                'event_amount_rupiah' => 50000,
                'customer_payment_id' => 'payment-2',
                'refund_id' => null,
                'source_table' => 'customer_payments',
                'source_id' => 'payment-2',
                'source_disposition_id' => null,
            ],
            [
                'note_id' => 'note-1',
                'event_date' => '2026-03-16',
                'event_type' => 'refund',
                'direction' => 'out',
                'event_amount_rupiah' => 10000,
                'customer_payment_id' => 'payment-1',
                'refund_id' => 'refund-1',
                'source_table' => 'customer_refunds',
                'source_id' => 'refund-1',
                'source_disposition_id' => null,
            ],
        ], $data['rows']);
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

    private function seedCustomerPayment(string $id, int $amountRupiah, string $paidAt): void
    {
        DB::table('customer_payments')->insert([
            'id' => $id,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
        ]);
    }

    private function seedPaymentAllocation(string $id, string $paymentId, string $noteId, int $amountRupiah): void
    {
        DB::table('payment_allocations')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
        ]);
    }

    private function seedCustomerRefund(
        string $id,
        string $paymentId,
        string $noteId,
        int $amountRupiah,
        string $refundedAt,
        string $reason,
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
