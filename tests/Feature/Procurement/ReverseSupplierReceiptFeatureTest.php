<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ReverseSupplierReceiptFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reverse_supplier_receipt_and_restore_inventory_precisely(): void
    {
        $this->seedReceivedInvoice();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-receipts.reverse.store', ['supplierReceiptId' => 'receipt-1']), [
                'reversed_at' => '2026-04-19',
                'reason' => 'Barang ternyata salah input penerimaan.',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHas('success', 'Reversal penerimaan supplier berhasil dicatat.');

        $this->assertDatabaseHas('supplier_receipt_reversals', [
            'supplier_receipt_id' => 'receipt-1',
            'reason' => 'Barang ternyata salah input penerimaan.',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'supplier_receipt_reversal_line',
            'source_id' => 'receipt-line-1',
            'tanggal_mutasi' => '2026-04-19',
            'qty_delta' => -5,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => -50000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 0,
        ]);

        $this->assertDatabaseMissing('product_inventory_costing', [
            'product_id' => 'product-1',
        ]);
    }

    public function test_admin_cannot_reverse_same_supplier_receipt_twice(): void
    {
        $this->seedReceivedInvoice();

        DB::table('supplier_receipt_reversals')->insert([
            'id' => 'reversal-1',
            'supplier_receipt_id' => 'receipt-1',
            'reason' => 'Sudah pernah direverse.',
            'performed_by_actor_id' => 'admin-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-receipts.reverse.store', ['supplierReceiptId' => 'receipt-1']), [
                'reversed_at' => '2026-04-19',
                'reason' => 'Coba reverse lagi.',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['supplier_receipt_reversal']);
    }

    public function test_admin_cannot_reverse_supplier_receipt_without_reason(): void
    {
        $this->seedReceivedInvoice();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-receipts.reverse.store', ['supplierReceiptId' => 'receipt-1']), [
                'reversed_at' => '2026-04-19',
                'reason' => '   ',
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['reason']);
    }

    private function seedReceivedInvoice(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-1',
            'nama_pt_pengirim' => 'PT Supplier Test',
            'nama_pt_pengirim_normalized' => 'pt supplier test',
        ]);

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 12000,
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);

        DB::table('supplier_invoices')->insert([
            'id' => 'invoice-1',
            'supplier_id' => 'supplier-1',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Supplier Test',
            'nomor_faktur' => 'INV-SUP-001',
            'nomor_faktur_normalized' => 'inv-sup-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'origin_supplier_invoice_id' => null,
            'superseded_by_supplier_invoice_id' => null,
            'tanggal_pengiriman' => '2026-04-18',
            'jatuh_tempo' => '2026-05-18',
            'grand_total_rupiah' => 50000,
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
            'product_ukuran_snapshot' => 100,
            'qty_pcs' => 5,
            'line_total_rupiah' => 50000,
            'unit_cost_rupiah' => 10000,
        ]);

        DB::table('supplier_receipts')->insert([
            'id' => 'receipt-1',
            'supplier_invoice_id' => 'invoice-1',
            'tanggal_terima' => '2026-04-18',
        ]);

        DB::table('supplier_receipt_lines')->insert([
            'id' => 'receipt-line-1',
            'supplier_receipt_id' => 'receipt-1',
            'supplier_invoice_line_id' => 'invoice-line-1',
            'product_id_snapshot' => 'product-1',
            'product_kode_barang_snapshot' => 'KB-001',
            'product_nama_barang_snapshot' => 'Ban Luar',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 100,
            'unit_cost_rupiah_snapshot' => 10000,
            'qty_diterima' => 5,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 5,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 50000,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'movement-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_receipt_line',
            'source_id' => 'receipt-line-1',
            'tanggal_mutasi' => '2026-04-18',
            'qty_delta' => 5,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => 50000,
        ]);
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Reverse Supplier Receipt',
            'email' => 'admin-reverse-supplier-receipt-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
