<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierPayment\SupplierPayment;
use App\Ports\Out\Procurement\SupplierPaymentWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierPaymentWriterAdapter implements SupplierPaymentWriterPort
{
    public function create(SupplierPayment $supplierPayment): void
    {
        DB::table('supplier_payments')->insert($this->toRecord($supplierPayment));
    }

    /**
     * @return array<string, string|int|null>
     */
    private function toRecord(SupplierPayment $supplierPayment): array
    {
        return [
            'id' => $supplierPayment->id(),
            'supplier_invoice_id' => $supplierPayment->supplierInvoiceId(),
            'amount_rupiah' => $supplierPayment->amountRupiah()->amount(),
            'paid_at' => $supplierPayment->paidAt()->format('Y-m-d'),
            'proof_status' => $supplierPayment->proofStatus(),
            'proof_storage_path' => $supplierPayment->proofStoragePath(),
        ];
    }
}
