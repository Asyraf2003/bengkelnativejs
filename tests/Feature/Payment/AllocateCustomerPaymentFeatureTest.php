<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\UseCases\AllocateCustomerPaymentHandler;
use App\Application\Shared\DTO\Result;
use App\Adapters\Out\Note\DatabaseNoteReaderAdapter;
use App\Adapters\Out\Payment\DatabaseCustomerPaymentReaderAdapter;
use App\Adapters\Out\Payment\DatabasePaymentAllocationReaderAdapter;
use App\Adapters\Out\Payment\DatabasePaymentAllocationWriterAdapter;
use App\Core\Payment\Policies\PaymentAllocationPolicy;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AllocateCustomerPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocate_customer_payment_handler_stores_allocation_and_calculates_partial_outstanding(): void
    {
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 100000);
        $this->seedCustomerPayment('payment-1', 150000, '2026-03-15');

        $handler = $this->buildHandler('allocation-1');

        $result = $handler->handle(
            'payment-1',
            'note-1',
            40000,
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $this->assertDatabaseCount('payment_allocations', 1);

        $this->assertDatabaseHas('payment_allocations', [
            'id' => 'allocation-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'amount_rupiah' => 40000,
        ]);

        $data = $result->data();

        $this->assertIsArray($data);
        $this->assertSame(110000, $data['payment']['remaining_allocatable_rupiah']);
        $this->assertSame(60000, $data['note_receivable']['outstanding_rupiah']);
        $this->assertFalse($data['note_receivable']['is_paid']);
    }

    public function test_allocate_customer_payment_handler_marks_note_as_paid_when_outstanding_reaches_zero(): void
    {
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 100000);
        $this->seedCustomerPayment('payment-1', 100000, '2026-03-15');

        $handler = $this->buildHandler('allocation-1');

        $result = $handler->handle(
            'payment-1',
            'note-1',
            100000,
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $data = $result->data();

        $this->assertIsArray($data);
        $this->assertSame(0, $data['payment']['remaining_allocatable_rupiah']);
        $this->assertSame(0, $data['note_receivable']['outstanding_rupiah']);
        $this->assertTrue($data['note_receivable']['is_paid']);
    }

    public function test_allocate_customer_payment_handler_rejects_invalid_target(): void
    {
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 100000);

        $handler = $this->buildHandler('allocation-1');

        $result = $handler->handle(
            'payment-missing',
            'note-1',
            50000,
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['payment' => ['PAYMENT_INVALID_TARGET']],
            $result->errors(),
        );

        $this->assertDatabaseCount('payment_allocations', 0);
    }

    public function test_allocate_customer_payment_handler_rejects_when_amount_exceeds_remaining_payment(): void
    {
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 200000);
        $this->seedCustomerPayment('payment-1', 50000, '2026-03-15');
        $this->seedPaymentAllocation('allocation-old', 'payment-1', 'note-old', 30000);

        $handler = $this->buildHandler('allocation-1');

        $result = $handler->handle(
            'payment-1',
            'note-1',
            25000,
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['payment' => ['PAYMENT_OVER_ALLOCATION']],
            $result->errors(),
        );

        $this->assertDatabaseCount('payment_allocations', 1);
        $this->assertDatabaseMissing('payment_allocations', [
            'id' => 'allocation-1',
        ]);
    }

    public function test_allocate_customer_payment_handler_rejects_when_amount_exceeds_note_outstanding(): void
    {
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 60000);
        $this->seedCustomerPayment('payment-1', 100000, '2026-03-15');
        $this->seedPaymentAllocation('allocation-old', 'payment-old', 'note-1', 50000);

        $handler = $this->buildHandler('allocation-1');

        $result = $handler->handle(
            'payment-1',
            'note-1',
            15000,
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['payment' => ['PAYMENT_EXCEEDS_OUTSTANDING']],
            $result->errors(),
        );

        $this->assertDatabaseCount('payment_allocations', 1);
        $this->assertDatabaseMissing('payment_allocations', [
            'id' => 'allocation-1',
        ]);
    }

    private function buildHandler(string $allocationId): AllocateCustomerPaymentHandler
    {
        return new AllocateCustomerPaymentHandler(
            new DatabaseCustomerPaymentReaderAdapter(),
            new DatabasePaymentAllocationReaderAdapter(),
            new DatabasePaymentAllocationWriterAdapter(),
            new DatabaseNoteReaderAdapter(),
            new PaymentAllocationPolicy(),
            new class () implements TransactionManagerPort {
                public function begin(): void
                {
                    DB::beginTransaction();
                }

                public function commit(): void
                {
                    DB::commit();
                }

                public function rollBack(): void
                {
                    DB::rollBack();
                }
            },
            new class ($allocationId) implements UuidPort {
                public function __construct(
                    private readonly string $allocationId,
                ) {
                }

                public function generate(): string
                {
                    return $this->allocationId;
                }
            },
        );
    }

    private function seedNote(
        string $id,
        string $customerName,
        string $transactionDate,
        int $totalRupiah,
    ): void {
        DB::table('notes')->insert([
            'id' => $id,
            'customer_name' => $customerName,
            'transaction_date' => $transactionDate,
            'total_rupiah' => $totalRupiah,
        ]);
    }

    private function seedCustomerPayment(
        string $id,
        int $amountRupiah,
        string $paidAt,
    ): void {
        DB::table('customer_payments')->insert([
            'id' => $id,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
        ]);
    }

    private function seedPaymentAllocation(
        string $id,
        string $customerPaymentId,
        string $noteId,
        int $amountRupiah,
    ): void {
        DB::table('payment_allocations')->insert([
            'id' => $id,
            'customer_payment_id' => $customerPaymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
        ]);
    }
}
