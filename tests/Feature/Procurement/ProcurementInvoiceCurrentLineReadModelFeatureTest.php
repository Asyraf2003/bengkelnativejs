<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProcurementInvoiceCurrentLineReadModelFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_page_shows_only_current_revision_lines(): void
    {
        $this->seedInvoiceWithHistoricalAndCurrentLines();

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.show', [
                'supplierInvoiceId' => 'invoice-1',
            ]));

        $response->assertOk();
        $response->assertSee('Ban Dalam');
        $response->assertDontSee('Ban Luar');
    }

    public function test_edit_page_shows_only_current_revision_lines(): void
    {
        $this->seedInvoiceWithHistoricalAndCurrentLines();

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.edit', [
                'supplierInvoiceId' => 'invoice-1',
            ]));

        $response->assertOk();
        $response->assertSee('Ban Dalam');
        $response->assertDontSee('Ban Luar');
    }

    private function seedInvoiceWithHistoricalAndCurrentLines(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-1',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'nama_pt_pengirim_normalized' => 'pt sumber makmur',
        ]);

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
        ]);

        DB::table('products')->insert([
            'id' => 'product-2',
            'kode_barang' => 'KB-002',
            'nama_barang' => 'Ban Dalam',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 38000,
        ]);

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-1',
            'supplier_id' => 'supplier-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Sumber Makmur',
            'nomor_faktur' => 'INV-SUP-001',
            'nomor_faktur_normalized' => 'inv-sup-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-03-15',
            'jatuh_tempo' => '2026-04-14',
            'grand_total_rupiah' => 24000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 2,
        ]);

        DB::table('supplier_invoice_lines')->insert([
            [
                'id' => 'invoice-line-1',
                'supplier_invoice_id' => 'invoice-1',
                'revision_no' => 1,
                'is_current' => 0,
                'source_line_id' => null,
                'superseded_by_line_id' => 'invoice-line-2',
                'superseded_at' => '2026-03-16 10:00:00',
                'line_no' => 1,
                'product_id' => 'product-1',
                'product_kode_barang_snapshot' => 'KB-001',
                'product_nama_barang_snapshot' => 'Ban Luar',
                'product_merek_snapshot' => 'Federal',
                'product_ukuran_snapshot' => 90,
                'qty_pcs' => 2,
                'line_total_rupiah' => 20000,
                'unit_cost_rupiah' => 10000,
            ],
            [
                'id' => 'invoice-line-2',
                'supplier_invoice_id' => 'invoice-1',
                'revision_no' => 2,
                'is_current' => 1,
                'source_line_id' => 'invoice-line-1',
                'superseded_by_line_id' => null,
                'superseded_at' => null,
                'line_no' => 1,
                'product_id' => 'product-2',
                'product_kode_barang_snapshot' => 'KB-002',
                'product_nama_barang_snapshot' => 'Ban Dalam',
                'product_merek_snapshot' => 'Federal',
                'product_ukuran_snapshot' => 90,
                'qty_pcs' => 2,
                'line_total_rupiah' => 24000,
                'unit_cost_rupiah' => 12000,
            ],
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-procurement-current-line-read@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
