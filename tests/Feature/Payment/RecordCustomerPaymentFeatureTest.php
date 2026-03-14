<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\UseCases\RecordCustomerPaymentHandler;
use App\Application\Shared\DTO\Result;
use App\Adapters\Out\Payment\DatabaseCustomerPaymentWriterAdapter;
use App\Ports\Out\UuidPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecordCustomerPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_customer_payment_handler_stores_new_payment(): void
    {
        $handler = new RecordCustomerPaymentHandler(
            new DatabaseCustomerPaymentWriterAdapter(),
            new class () implements UuidPort {
                public function generate(): string
                {
                    return 'payment-1';
                }
            },
        );

        $result = $handler->handle(
            150000,
            '2026-03-15',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $this->assertDatabaseCount('customer_payments', 1);

        $this->assertDatabaseHas('customer_payments', [
            'id' => 'payment-1',
            'amount_rupiah' => 150000,
            'paid_at' => '2026-03-15',
        ]);
    }

    public function test_record_customer_payment_handler_rejects_zero_amount(): void
    {
        $handler = new RecordCustomerPaymentHandler(
            new DatabaseCustomerPaymentWriterAdapter(),
            new class () implements UuidPort {
                public function generate(): string
                {
                    return 'payment-1';
                }
            },
        );

        $result = $handler->handle(
            0,
            '2026-03-15',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['payment' => ['INVALID_CUSTOMER_PAYMENT']],
            $result->errors(),
        );

        $this->assertDatabaseCount('customer_payments', 0);
    }

    public function test_record_customer_payment_handler_rejects_invalid_paid_at(): void
    {
        $handler = new RecordCustomerPaymentHandler(
            new DatabaseCustomerPaymentWriterAdapter(),
            new class () implements UuidPort {
                public function generate(): string
                {
                    return 'payment-1';
                }
            },
        );

        $result = $handler->handle(
            150000,
            '15-03-2026',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['payment' => ['INVALID_CUSTOMER_PAYMENT']],
            $result->errors(),
        );

        $this->assertDatabaseCount('customer_payments', 0);
    }
}
