<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use App\Ports\Out\UuidPort;

final class SupplierInvoiceFactory
{
    public function __construct(
        private ProductReaderPort $products,
        private UuidPort $uuid
    ) {}

    public function makeLines(array $lines): array
    {
        if ($lines === []) throw new DomainException('Invoice minimal 1 line.');

        return array_map(function ($l) {
            $pId = trim((string)($l['product_id'] ?? ''));
            if ($pId === '' || !$this->products->getById($pId)) {
                throw new DomainException('Product tidak ditemukan.');
            }

            return SupplierInvoiceLine::create(
                $this->uuid->generate(),
                $pId,
                (int)($l['qty_pcs'] ?? 0),
                Money::fromInt((int)($l['line_total_rupiah'] ?? 0))
            );
        }, $lines);
    }
}
