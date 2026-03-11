<?php

declare(strict_types=1);

namespace App\Core\Inventory\ProductInventory;

use App\Core\Shared\Exceptions\DomainException;

final class ProductInventory
{
    private function __construct(
        private string $productId,
        private int $qtyOnHand,
    ) {
    }

    public static function create(
        string $productId,
        int $qtyOnHand = 0,
    ): self {
        self::assertValid($productId, $qtyOnHand);

        return new self(
            trim($productId),
            $qtyOnHand,
        );
    }

    public static function rehydrate(
        string $productId,
        int $qtyOnHand,
    ): self {
        self::assertValid($productId, $qtyOnHand);

        return new self(
            trim($productId),
            $qtyOnHand,
        );
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function qtyOnHand(): int
    {
        return $this->qtyOnHand;
    }

    public function increase(int $qty): void
    {
        if ($qty <= 0) {
            throw new DomainException('Qty penambahan stok harus lebih besar dari nol.');
        }

        $this->qtyOnHand += $qty;
    }

    private static function assertValid(
        string $productId,
        int $qtyOnHand,
    ): void {
        if (trim($productId) === '') {
            throw new DomainException('Product id pada inventory wajib ada.');
        }

        if ($qtyOnHand < 0) {
            throw new DomainException('Qty on hand tidak boleh negatif.');
        }
    }
}
