<?php

namespace App\Application\UseCases\Invoices;

use App\Models\SupplierInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MarkPaidSupplierInvoiceUseCase
{
    /**
     * @param array{
     *   invoice_id:int,
     *   paid_at:string
     * } $input
     */
    public function execute(array $input): void
    {
        DB::transaction(function () use ($input) {
            $invoice = SupplierInvoice::query()
                ->lockForUpdate()
                ->findOrFail((int) $input['invoice_id']);

            if ((bool) $invoice->is_paid) {
                throw new \DomainException('Faktur ini sudah ditandai paid.');
            }

            $paidAt = CarbonImmutable::parse($input['paid_at'])->startOfDay();

            $invoice->is_paid = true;
            $invoice->paid_at = $paidAt->toDateString();
            $invoice->save();
        });
    }
}
