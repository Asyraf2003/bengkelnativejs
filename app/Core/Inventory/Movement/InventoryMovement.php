<?php

declare(strict_types=1);

namespace App\Core\Inventory\Movement;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;

final class InventoryMovement
{
    private function __construct(
        private string $id,
        private string $productId,
        private string $movementType,
        private string $sourceType,
        private string $sourceId,
        private DateTimeImmutable $tanggalMutasi,
        private int $qtyDelta,
        private Money $unitCostRupiah,
        private Money $totalCostRupiah,
    ) {
    }

    public static function create(
        string $id,
        string $productId,
        string $movementType,
        string $sourceType,
        string $sourceId,
        DateTimeImmutable $tanggalMutasi,
        int $qtyDelta,
        Money $unitCostRupiah,
    ): self {
        self::assertValid(
            $id,
            $productId,
            $movementType,
            $sourceType,
            $sourceId,
            $qtyDelta,
            $unitCostRupiah,
        );

        return new self(
            $id,
            trim($productId),
            trim($movementType),
            trim($sourceType),
            trim($sourceId),
            $tanggalMutasi,
            $qtyDelta,
            $unitCostRupiah,
            $unitCostRupiah->multiply($qtyDelta),
        );
    }

    public static function rehydrate(
        string $id,
        string $productId,
        string $movementType,
        string $sourceType,
        string $sourceId,
        DateTimeImmutable $tanggalMutasi,
        int $qtyDelta,
        Money $unitCostRupiah,
    ): self {
        self::assertValid(
            $id,
            $productId,
            $movementType,
            $sourceType,
            $sourceId,
            $qtyDelta,
            $unitCostRupiah,
        );

        return new self(
            $id,
            trim($productId),
            trim($movementType),
            trim($sourceType),
            trim($sourceId),
            $tanggalMutasi,
            $qtyDelta,
            $unitCostRupiah,
            $unitCostRupiah->multiply($qtyDelta),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function movementType(): string
    {
        return $this->movementType;
    }

    public function sourceType(): string
    {
        return $this->sourceType;
    }

    public function sourceId(): string
    {
        return $this->sourceId;
    }

    public function tanggalMutasi(): DateTimeImmutable
    {
        return $this->tanggalMutasi;
    }

    public function qtyDelta(): int
    {
        return $this->qtyDelta;
    }

    public function unitCostRupiah(): Money
    {
        return $this->unitCostRupiah;
    }

    public function totalCostRupiah(): Money
    {
        return $this->totalCostRupiah;
    }

    private static function assertValid(
        string $id,
        string $productId,
        string $movementType,
        string $sourceType,
        string $sourceId,
        int $qtyDelta,
        Money $unitCostRupiah,
    ): void {
        if (trim($id) === '') {
            throw new DomainException('Inventory movement id wajib ada.');
        }

        if (trim($productId) === '') {
            throw new DomainException('Product id pada inventory movement wajib ada.');
        }

        if (trim($movementType) === '') {
            throw new DomainException('Movement type wajib ada.');
        }

        if (trim($sourceType) === '') {
            throw new DomainException('Source type wajib ada.');
        }

        if (trim($sourceId) === '') {
            throw new DomainException('Source id wajib ada.');
        }

        if ($qtyDelta === 0) {
            throw new DomainException('Qty delta tidak boleh nol.');
        }

        if ($unitCostRupiah->isNegative()) {
            throw new DomainException('Unit cost rupiah tidak boleh negatif.');
        }
    }
}
