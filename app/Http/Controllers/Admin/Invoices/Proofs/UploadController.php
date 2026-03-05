<?php

namespace App\Http\Controllers\Admin\Invoices\Proofs;

use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController
{
    public function __invoke(int $invoice, Request $request)
    {
        $invoice = SupplierInvoice::query()->findOrFail($invoice);

        $data = $request->validate([
            // multi file: proofs[]
            'proofs'   => ['required', 'array', 'min:1'],
            'proofs.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png'], // 20MB default
        ]);

        /** @var array<int, \Illuminate\Http\UploadedFile> $files */
        $files = $data['proofs'];

        foreach ($files as $file) {
            $uuid = (string) Str::uuid();
            $ext  = $file->getClientOriginalExtension() ?: 'bin';

            $dir  = "invoices/{$invoice->id}";
            $name = "{$uuid}.{$ext}";
            $path = $file->storeAs($dir, $name, ['disk' => 'local']); // storage/app/...

            SupplierInvoiceMedia::query()->create([
                'supplier_invoice_id' => $invoice->id,
                'path_private'         => $path,
                'original_name'        => $file->getClientOriginalName(),
                'mime'                 => (string) $file->getClientMimeType(),
                'size'                 => (int) $file->getSize(),
                'uploaded_by'          => (int) Auth::id(),
                'uploaded_at'          => now(),
            ]);
        }

        return redirect()
            ->route('admin.invoices.proofs.index', $invoice->id)
            ->with('status', 'Bukti bayar tersimpan.');
    }
}
