<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

use App\Core\Procurement\SupplierInvoice\SupplierInvoice;

interface SupplierInvoiceReaderPort
{
    public function getById(string $supplierInvoiceId): ?SupplierInvoice;
}
