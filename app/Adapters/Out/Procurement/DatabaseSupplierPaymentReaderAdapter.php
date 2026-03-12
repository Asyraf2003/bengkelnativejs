<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Procurement\SupplierPaymentReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierPaymentReaderAdapter implements SupplierPaymentReaderPort
{
    public function getTotalPaidBySupplierInvoiceId(string $supplierInvoiceId): Money
    {
        $totalPaid = (int) DB::table('supplier_payments')
            ->where('supplier_invoice_id', $supplierInvoiceId)
            ->sum('amount_rupiah');

        return Money::fromInt($totalPaid);
    }
}
