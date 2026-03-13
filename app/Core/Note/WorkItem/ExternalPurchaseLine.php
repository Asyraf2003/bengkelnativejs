<?php

declare(strict_types=1);

namespace App\Core\Note\WorkItem;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

final class ExternalPurchaseLine
{
    private function __construct(
        private string $id,
        private string $costDescription,
        private Money $unitCostRupiah,
        private int $qty,
        private Money $lineTotalRupiah,
    ) {
    }

    public static function create(
        string $id,
        string $costDescription,
        Money $unitCostRupiah,
        int $qty,
    ): self {
        self::assertValid($id, $costDescription, $unitCostRupiah, $qty);

        return new self(
            trim($id),
            trim($costDescription),
            $unitCostRupiah,
            $qty,
            $unitCostRupiah->multiply($qty),
        );
    }

    public static function rehydrate(
        string $id,
        string $costDescription,
        Money $unitCostRupiah,
        int $qty,
    ): self {
        self::assertValid($id, $costDescription, $unitCostRupiah, $qty);

        return new self(
            trim($id),
            trim($costDescription),
            $unitCostRupiah,
            $qty,
            $unitCostRupiah->multiply($qty),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function costDescription(): string
    {
        return $this->costDescription;
    }

    public function unitCostRupiah(): Money
    {
        return $this->unitCostRupiah;
    }

    public function qty(): int
    {
        return $this->qty;
    }

    public function lineTotalRupiah(): Money
    {
        return $this->lineTotalRupiah;
    }

    private static function assertValid(
        string $id,
        string $costDescription,
        Money $unitCostRupiah,
        int $qty,
    ): void {
        if (trim($id) === '') {
            throw new DomainException('External purchase line id wajib ada.');
        }

        if (trim($costDescription) === '') {
            throw new DomainException('Cost description pada external purchase line wajib ada.');
        }

        if ($unitCostRupiah->greaterThan(Money::zero()) === false) {
            throw new DomainException('Unit cost rupiah pada external purchase line harus lebih besar dari nol.');
        }

        if ($qty <= 0) {
            throw new DomainException('Qty pada external purchase line harus lebih besar dari nol.');
        }
    }
}
