<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ReviseReceivedSupplierInvoiceNegativeStockGuardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_revise_received_invoice_when_delta_minus_would_make_old_product_negative(): void
    {
        $this->seedReceivedInvoice();
        $this->seedReplacementProduct();

        DB::table('inventory_movements')->insert([
            'id' => 'movement-sale-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'customer_transaction_line',
            'source_id' => 'trx-line-1',
            'tanggal_mutasi' => '2026-03-17',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => -20000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 0,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 0,
            'inventory_value_rupiah' => 0,
        ]);

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.revise', [
                'supplierInvoiceId' => 'invoice-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), [
                'expected_revision_no' => 1,
                'change_reason' => 'Mau ganti product lama yang stoknya sudah habis terpakai.',
                'nomor_faktur' => 'INV-SUP-001',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-15',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-2',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 24000,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.revise', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
        $response->assertSessionHasErrors([
            'supplier_invoice' => 'Revisi faktur akan membuat stok product lama menjadi negatif.',
        ]);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'last_revision_no' => 1,
            'grand_total_rupiah' => 20000,
        ]);

        $this->assertDatabaseMissing('inventory_movements', [
            'source_type' => 'supplier_invoice_revision_delta_line',
        ]);
    }

    private function seedReceivedInvoice(): void
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
            'grand_total_rupiah' => 20000,
            'voided_at' => null,
            'void_reason' => null,
            'last_revision_no' => 1,
        ]);

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'invoice-line-1',
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 1,
            'is_current' => 1,
            'source_line_id' => null,
            'superseded_by_line_id' => null,
            'superseded_at' => null,
            'line_no' => 1,
            'product_id' => 'product-1',
            'product_kode_barang_snapshot' => 'KB-001',
            'product_nama_barang_snapshot' => 'Ban Luar',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'qty_pcs' => 2,
            'line_total_rupiah' => 20000,
            'unit_cost_rupiah' => 10000,
        ]);

        DB::table('supplier_receipts')->insert([
            'id' => 'receipt-1',
            'supplier_invoice_id' => 'invoice-1',
            'tanggal_terima' => '2026-03-16',
        ]);

        DB::table('supplier_receipt_lines')->insert([
            'id' => 'receipt-line-1',
            'supplier_receipt_id' => 'receipt-1',
            'supplier_invoice_line_id' => 'invoice-line-1',
            'product_id_snapshot' => 'product-1',
            'product_kode_barang_snapshot' => 'KB-001',
            'product_nama_barang_snapshot' => 'Ban Luar',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 90,
            'unit_cost_rupiah_snapshot' => 10000,
            'qty_diterima' => 2,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'movement-receipt-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_receipt_line',
            'source_id' => 'receipt-line-1',
            'tanggal_mutasi' => '2026-03-16',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => 20000,
        ]);
    }

    private function seedReplacementProduct(): void
    {
        // product-2 sudah dibuat di seedReceivedInvoice untuk menjaga setup tetap sederhana
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-procurement-negative-stock-guard@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
