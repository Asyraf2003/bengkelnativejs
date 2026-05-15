<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ReviseReceivedSupplierInvoiceDeltaFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_revise_received_invoice_by_changing_product_on_same_invoice_id(): void
    {
        $this->seedReceivedInvoice();
        $this->seedReplacementProduct();

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), [
                'expected_revision_no' => 1,
                'change_reason' => 'Salah input product di faktur supplier.',
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

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
        $response->assertSessionHas('success', 'Nota supplier berhasil diperbarui.');

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'last_revision_no' => 2,
            'grand_total_rupiah' => 24000,
        ]);

        $this->assertDatabaseHas('supplier_invoice_lines', [
            'id' => 'invoice-line-1',
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 1,
            'is_current' => 0,
        ]);

        $this->assertDatabaseHas('supplier_invoice_lines', [
            'supplier_invoice_id' => 'invoice-1',
            'revision_no' => 2,
            'is_current' => 1,
            'line_no' => 1,
            'product_id' => 'product-2',
            'qty_pcs' => 2,
            'line_total_rupiah' => 24000,
            'unit_cost_rupiah' => 12000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'supplier_invoice_revision_delta_line',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => -20000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-2',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_invoice_revision_delta_line',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 12000,
            'total_cost_rupiah' => 24000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 0,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-2',
            'qty_on_hand' => 2,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-2',
            'avg_cost_rupiah' => 12000,
            'inventory_value_rupiah' => 24000,
        ]);

        $this->assertDatabaseMissing('product_inventory_costing', [
            'product_id' => 'product-1',
        ]);
    }

    public function test_admin_cannot_revise_received_invoice_when_revised_total_is_below_total_paid(): void
    {
        $this->seedReceivedInvoice();
        $this->seedPayment();

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.revise', [
                'supplierInvoiceId' => 'invoice-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), [
                'expected_revision_no' => 1,
                'change_reason' => 'Mau turunkan total faktur di bawah pembayaran existing.',
                'nomor_faktur' => 'INV-SUP-001',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-15',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 1,
                        'line_total_rupiah' => 4000,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.revise', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
        $response->assertSessionHasErrors([
            'supplier_invoice' => 'Total revisi tidak boleh lebih kecil dari total pembayaran yang sudah tercatat.',
        ]);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'last_revision_no' => 1,
            'grand_total_rupiah' => 20000,
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

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 2,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 20000,
        ]);
    }

    private function seedReplacementProduct(): void
    {
        DB::table('products')->insert([
            'id' => 'product-2',
            'kode_barang' => 'KB-002',
            'nama_barang' => 'Ban Dalam',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 38000,
        ]);
    }

    private function seedPayment(): void
    {
        DB::table('supplier_payments')->insert([
            'id' => 'payment-1',
            'supplier_invoice_id' => 'invoice-1',
            'amount_rupiah' => 5000,
            'paid_at' => '2026-03-16',
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-procurement-received-revision@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
