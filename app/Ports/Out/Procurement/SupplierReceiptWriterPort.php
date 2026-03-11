<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

use App\Core\Procurement\SupplierReceipt\SupplierReceipt;

interface SupplierReceiptWriterPort
{
    public function create(SupplierReceipt $supplierReceipt): void;
}
