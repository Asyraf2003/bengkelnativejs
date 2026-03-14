<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Payment\CustomerPayment;

use App\Core\Payment\CustomerPayment\CustomerPayment;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CustomerPaymentTest extends TestCase
{
    public function test_it_creates_customer_payment_with_valid_data(): void
    {
        $payment = CustomerPayment::create(
            'payment-1',
            Money::fromInt(150000),
            new DateTimeImmutable('2026-03-15'),
        );

        $this->assertSame('payment-1', $payment->id());
        $this->assertSame(150000, $payment->amountRupiah()->amount());
        $this->assertSame('2026-03-15', $payment->paidAt()->format('Y-m-d'));
    }

    public function test_it_rehydrates_customer_payment_with_valid_data(): void
    {
        $payment = CustomerPayment::rehydrate(
            'payment-1',
            Money::fromInt(80000),
            new DateTimeImmutable('2026-03-15'),
        );

        $this->assertSame('payment-1', $payment->id());
        $this->assertSame(80000, $payment->amountRupiah()->amount());
        $this->assertSame('2026-03-15', $payment->paidAt()->format('Y-m-d'));
    }

    public function test_it_rejects_blank_id(): void
    {
        $this->expectException(DomainException::class);

        CustomerPayment::create(
            '   ',
            Money::fromInt(100000),
            new DateTimeImmutable('2026-03-15'),
        );
    }

    public function test_it_rejects_zero_or_negative_amount(): void
    {
        $this->expectException(DomainException::class);

        CustomerPayment::create(
            'payment-1',
            Money::fromInt(0),
            new DateTimeImmutable('2026-03-15'),
        );
    }
}
