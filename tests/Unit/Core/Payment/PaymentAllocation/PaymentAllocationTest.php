<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Payment\PaymentAllocation;

use App\Core\Payment\PaymentAllocation\PaymentAllocation;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class PaymentAllocationTest extends TestCase
{
    public function test_it_creates_payment_allocation_with_valid_data(): void
    {
        $allocation = PaymentAllocation::create(
            'allocation-1',
            'payment-1',
            'note-1',
            Money::fromInt(50000),
        );

        $this->assertSame('allocation-1', $allocation->id());
        $this->assertSame('payment-1', $allocation->customerPaymentId());
        $this->assertSame('note-1', $allocation->noteId());
        $this->assertSame(50000, $allocation->amountRupiah()->amount());
    }

    public function test_it_rehydrates_payment_allocation_with_valid_data(): void
    {
        $allocation = PaymentAllocation::rehydrate(
            'allocation-1',
            'payment-1',
            'note-1',
            Money::fromInt(25000),
        );

        $this->assertSame('allocation-1', $allocation->id());
        $this->assertSame('payment-1', $allocation->customerPaymentId());
        $this->assertSame('note-1', $allocation->noteId());
        $this->assertSame(25000, $allocation->amountRupiah()->amount());
    }

    public function test_it_rejects_blank_id(): void
    {
        $this->expectException(DomainException::class);

        PaymentAllocation::create(
            '   ',
            'payment-1',
            'note-1',
            Money::fromInt(50000),
        );
    }

    public function test_it_rejects_blank_customer_payment_id(): void
    {
        $this->expectException(DomainException::class);

        PaymentAllocation::create(
            'allocation-1',
            '   ',
            'note-1',
            Money::fromInt(50000),
        );
    }

    public function test_it_rejects_blank_note_id(): void
    {
        $this->expectException(DomainException::class);

        PaymentAllocation::create(
            'allocation-1',
            'payment-1',
            '   ',
            Money::fromInt(50000),
        );
    }

    public function test_it_rejects_zero_or_negative_amount(): void
    {
        $this->expectException(DomainException::class);

        PaymentAllocation::create(
            'allocation-1',
            'payment-1',
            'note-1',
            Money::fromInt(0),
        );
    }
}
