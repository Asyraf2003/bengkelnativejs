<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierReceipt;

use App\Core\Shared\Exceptions\DomainException;
use DateTimeImmutable;

final class SupplierReceipt
{
    /** @param list<SupplierReceiptLine> $lines */
    private function __construct(
        private string $id,
        private string $supplierInvoiceId,
        private DateTimeImmutable $tanggalTerima,
        private array $lines,
    ) {}

    /** @param list<SupplierReceiptLine> $lines */
    public static function create(string $id, string $invId, DateTimeImmutable $tgl, array $lines): self
    {
        self::assertValid($id, $invId, $lines);
        return new self(trim($id), trim($invId), $tgl, array_values($lines));
    }

    /** @param list<SupplierReceiptLine> $lines */
    public static function rehydrate(string $id, string $invId, DateTimeImmutable $tgl, array $lines): self
    {
        self::assertValid($id, $invId, $lines);
        return new self(trim($id), trim($invId), $tgl, array_values($lines));
    }

    public function id(): string { return $this->id; }
    public function supplierInvoiceId(): string { return $this->supplierInvoiceId; }
    public function tanggalTerima(): DateTimeImmutable { return $this->tanggalTerima; }
    /** @return list<SupplierReceiptLine> */
    public function lines(): array { return $this->lines; }

    /** @param list<SupplierReceiptLine> $lines */
    private static function assertValid(string $id, string $invId, array $lines): void
    {
        if (trim($id) === '') throw new DomainException('ID wajib ada.');
        if (trim($invId) === '') throw new DomainException('Invoice ID wajib ada.');
        if ($lines === []) throw new DomainException('Minimal 1 line.');
        foreach ($lines as $l) {
            if (!$l instanceof SupplierReceiptLine) throw new DomainException('Line tidak valid.');
        }
    }
}
