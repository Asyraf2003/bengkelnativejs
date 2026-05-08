<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Procurement\LaravelSupplierPaymentProofFileStorageAdapter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SupplierPaymentProofFileStorageAdapterFeatureTest extends TestCase
{
    public function test_store_many_uses_server_detected_mime_instead_of_client_controlled_mime(): void
    {
        Storage::fake('local');

        $sourcePath = tempnam(sys_get_temp_dir(), 'hyperpos-proof-');
        self::assertIsString($sourcePath);

        file_put_contents(
            $sourcePath,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
        );

        try {
            $storedFiles = (new LaravelSupplierPaymentProofFileStorageAdapter())->storeMany('payment-1', [
                [
                    'source_path' => $sourcePath,
                    'original_filename' => 'proof.pdf',
                    'mime_type' => 'text/html',
                    'file_size_bytes' => filesize($sourcePath),
                ],
            ]);
        } finally {
            @unlink($sourcePath);
        }

        self::assertCount(1, $storedFiles);
        self::assertSame('application/pdf', $storedFiles[0]['mime_type']);
        self::assertStringStartsWith('supplier-payment-proofs/payment-1/', $storedFiles[0]['storage_path']);
        self::assertTrue(Storage::disk('local')->exists((string) $storedFiles[0]['storage_path']));
    }
}
