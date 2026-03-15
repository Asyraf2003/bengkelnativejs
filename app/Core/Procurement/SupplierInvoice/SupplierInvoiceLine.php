<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierInvoice;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

final class SupplierInvoiceLine
{
    private function __construct(
        private string $id,
        private string $productId,
        private int $qtyPcs,
        private Money $lineTotalRupiah,
        private Money $unitCostRupiah,
    ) {}

    public static function create(string $id, string $pId, int $qty, Money $total): self
    {
        self::assertValid($id, $pId, $qty, $total);
        $unitCost = Money::fromInt(intdiv($total->amount(), $qty));
        return new self(trim($id), trim($pId), $qty, $total, $unitCost);
    }

    public static function rehydrate(string $id, string $pId, int $qty, Money $total): self
    {
        self::assertValid($id, $pId, $qty, $total);
        $unitCost = Money::fromInt(intdiv($total->amount(), $qty));
        return new self(trim($id), trim($pId), $qty, $total, $unitCost);
    }

    public function id(): string { return $this->id; }
    public function productId(): string { return $this->productId; }
    public function qtyPcs(): int { return $this->qtyPcs; }
    public function lineTotalRupiah(): Money { return $this->lineTotalRupiah; }
    public function unitCostRupiah(): Money { return $this->unitCostRupiah; }

    private static function assertValid(string $id, string $pId, int $qty, Money $total): void
    {
        if (trim($id) === '') throw new DomainException('ID wajib ada.');
        if (trim($pId) === '') throw new DomainException('Product ID wajib ada.');
        if ($qty <= 0) throw new DomainException('Qty harus > 0.');
        if (!$total->greaterThan(Money::zero())) throw new DomainException('Total harus > 0.');
        if ($total->amount() % $qty !== 0) throw new DomainException('Total harus habis dibagi qty.');
    }
}
