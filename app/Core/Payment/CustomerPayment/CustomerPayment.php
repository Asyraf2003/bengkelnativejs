<?php

declare(strict_types=1);

namespace App\Core\Payment\CustomerPayment;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;

final class CustomerPayment
{
    private function __construct(
        private string $id,
        private Money $amountRupiah,
        private DateTimeImmutable $paidAt,
    ) {
    }

    public static function create(
        string $id,
        Money $amountRupiah,
        DateTimeImmutable $paidAt,
    ): self {
        self::assertValid($id, $amountRupiah);

        return new self(
            trim($id),
            $amountRupiah,
            $paidAt,
        );
    }

    public static function rehydrate(
        string $id,
        Money $amountRupiah,
        DateTimeImmutable $paidAt,
    ): self {
        self::assertValid($id, $amountRupiah);

        return new self(
            trim($id),
            $amountRupiah,
            $paidAt,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function amountRupiah(): Money
    {
        return $this->amountRupiah;
    }

    public function paidAt(): DateTimeImmutable
    {
        return $this->paidAt;
    }

    private static function assertValid(
        string $id,
        Money $amountRupiah,
    ): void {
        if (trim($id) === '') {
            throw new DomainException('Customer payment id wajib ada.');
        }

        if ($amountRupiah->greaterThan(Money::zero()) === false) {
            throw new DomainException('Amount rupiah pada customer payment harus lebih besar dari nol.');
        }
    }
}
