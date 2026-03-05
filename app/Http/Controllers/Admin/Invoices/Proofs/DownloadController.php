<?php

namespace App\Http\Controllers\Admin\Invoices\Proofs;

use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceMedia;
use Illuminate\Support\Facades\Storage;

class DownloadController
{
    public function __invoke(int $invoice, int $media)
    {
        $invoice = SupplierInvoice::query()->findOrFail($invoice);

        $media = SupplierInvoiceMedia::query()->findOrFail($media);

        // guard: media harus milik invoice tsb
        if ((int) $media->supplier_invoice_id !== (int) $invoice->id) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($media->path_private)) {
            abort(404);
        }

        return Storage::disk('local')->download($media->path_private, $media->original_name);
    }
}
