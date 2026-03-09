<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Shared\ValueObjects;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_it_keeps_integer_amount_exactly(): void
    {
        $money = Money::fromInt(15000);

        $this->assertSame(15000, $money->amount());
    }

    public function test_it_can_add_without_losing_one_rupiah(): void
    {
        $left = Money::fromInt(10001);
        $right = Money::fromInt(4999);

        $result = $left->add($right);

        $this->assertSame(15000, $result->amount());
    }

    public function test_it_can_subtract_without_losing_one_rupiah(): void
    {
        $left = Money::fromInt(15000);
        $right = Money::fromInt(1);

        $result = $left->subtract($right);

        $this->assertSame(14999, $result->amount());
    }

    public function test_it_can_multiply_by_integer(): void
    {
        $money = Money::fromInt(15000);

        $result = $money->multiply(3);

        $this->assertSame(45000, $result->amount());
    }

    public function test_it_detects_zero_and_negative(): void
    {
        $zero = Money::zero();
        $negative = Money::fromInt(-1);

        $this->assertTrue($zero->isZero());
        $this->assertTrue($negative->isNegative());
    }

    public function test_it_can_enforce_not_negative_when_requested(): void
    {
        $this->expectException(DomainException::class);

        Money::fromInt(-1)->ensureNotNegative();
    }
}
