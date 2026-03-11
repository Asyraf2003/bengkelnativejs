<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierReceipt;

use App\Core\Shared\Exceptions\DomainException;
use DateTimeImmutable;

final class SupplierReceipt
{
    /**
     * @param list<SupplierReceiptLine> $lines
     */
    private function __construct(
        private string $id,
        private string $supplierInvoiceId,
        private DateTimeImmutable $tanggalTerima,
        private array $lines,
    ) {
    }

    /**
     * @param list<SupplierReceiptLine> $lines
     */
    public static function create(
        string $id,
        string $supplierInvoiceId,
        DateTimeImmutable $tanggalTerima,
        array $lines,
    ): self {
        self::assertValid($id, $supplierInvoiceId, $lines);

        return new self(
            $id,
            trim($supplierInvoiceId),
            $tanggalTerima,
            array_values($lines),
        );
    }

    /**
     * @param list<SupplierReceiptLine> $lines
     */
    public static function rehydrate(
        string $id,
        string $supplierInvoiceId,
        DateTimeImmutable $tanggalTerima,
        array $lines,
    ): self {
        self::assertValid($id, $supplierInvoiceId, $lines);

        return new self(
            $id,
            trim($supplierInvoiceId),
            $tanggalTerima,
            array_values($lines),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function supplierInvoiceId(): string
    {
        return $this->supplierInvoiceId;
    }

    public function tanggalTerima(): DateTimeImmutable
    {
        return $this->tanggalTerima;
    }

    /**
     * @return list<SupplierReceiptLine>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    /**
     * @param list<SupplierReceiptLine> $lines
     */
    private static function assertValid(
        string $id,
        string $supplierInvoiceId,
        array $lines,
    ): void {
        if (trim($id) === '') {
            throw new DomainException('Supplier receipt id wajib ada.');
        }

        if (trim($supplierInvoiceId) === '') {
            throw new DomainException('Supplier invoice id wajib ada pada supplier receipt.');
        }

        if ($lines === []) {
            throw new DomainException('Supplier receipt minimal harus memiliki satu line.');
        }

        foreach ($lines as $line) {
            if ($line instanceof SupplierReceiptLine === false) {
                throw new DomainException('Line supplier receipt tidak valid.');
            }
        }
    }
}
