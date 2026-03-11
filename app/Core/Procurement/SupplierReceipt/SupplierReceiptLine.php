<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierReceipt;

use App\Core\Shared\Exceptions\DomainException;

final class SupplierReceiptLine
{
    private function __construct(
        private string $id,
        private string $supplierInvoiceLineId,
        private int $qtyDiterima,
    ) {
    }

    public static function create(
        string $id,
        string $supplierInvoiceLineId,
        int $qtyDiterima,
    ): self {
        self::assertValid($id, $supplierInvoiceLineId, $qtyDiterima);

        return new self(
            $id,
            trim($supplierInvoiceLineId),
            $qtyDiterima,
        );
    }

    public static function rehydrate(
        string $id,
        string $supplierInvoiceLineId,
        int $qtyDiterima,
    ): self {
        self::assertValid($id, $supplierInvoiceLineId, $qtyDiterima);

        return new self(
            $id,
            trim($supplierInvoiceLineId),
            $qtyDiterima,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function supplierInvoiceLineId(): string
    {
        return $this->supplierInvoiceLineId;
    }

    public function qtyDiterima(): int
    {
        return $this->qtyDiterima;
    }

    private static function assertValid(
        string $id,
        string $supplierInvoiceLineId,
        int $qtyDiterima,
    ): void {
        if (trim($id) === '') {
            throw new DomainException('Supplier receipt line id wajib ada.');
        }

        if (trim($supplierInvoiceLineId) === '') {
            throw new DomainException('Supplier invoice line id wajib ada.');
        }

        if ($qtyDiterima <= 0) {
            throw new DomainException('Qty diterima harus lebih besar dari nol.');
        }
    }
}
