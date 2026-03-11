<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

interface SupplierInvoiceLineReaderPort
{
    /**
     * @return list<array{
     *     id: string,
     *     supplier_invoice_id: string,
     *     product_id: string,
     *     qty_pcs: int,
     *     line_total_rupiah: int,
     *     unit_cost_rupiah: int
     * }>
     */
    public function getBySupplierInvoiceId(string $supplierInvoiceId): array;
}
