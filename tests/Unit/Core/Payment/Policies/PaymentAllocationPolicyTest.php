<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Payment\Policies;

use App\Core\Payment\Policies\PaymentAllocationPolicy;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class PaymentAllocationPolicyTest extends TestCase
{
    public function test_it_allows_allocation_when_amount_fits_remaining_payment_and_note_outstanding(): void
    {
        $policy = new PaymentAllocationPolicy();

        $policy->assertAllocatable(
            Money::fromInt(50000),
            Money::fromInt(150000),
            Money::fromInt(25000),
            Money::fromInt(120000),
            Money::fromInt(30000),
        );

    }

    public function test_it_rejects_allocation_when_amount_exceeds_remaining_payment(): void
    {
        $policy = new PaymentAllocationPolicy();

        $this->expectException(DomainException::class);

        $policy->assertAllocatable(
            Money::fromInt(90000),
            Money::fromInt(100000),
            Money::fromInt(20000),
            Money::fromInt(200000),
            Money::fromInt(10000),
        );
    }

    public function test_it_rejects_allocation_when_amount_exceeds_note_outstanding(): void
    {
        $policy = new PaymentAllocationPolicy();

        $this->expectException(DomainException::class);

        $policy->assertAllocatable(
            Money::fromInt(70000),
            Money::fromInt(200000),
            Money::fromInt(10000),
            Money::fromInt(100000),
            Money::fromInt(40000),
        );
    }
}
