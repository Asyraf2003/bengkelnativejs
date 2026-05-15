<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VoidSupplierInvoiceMutationGuardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_update_voided_supplier_invoice(): void
    {
        $this->seedVoidedInvoice();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), [
                'expected_revision_no' => 1,
                'change_reason' => 'Coba revise invoice voided.',
                'nomor_faktur' => 'INV-SUP-001-EDIT',
                'nama_pt_pengirim' => 'PT Supplier Test',
                'tanggal_pengiriman' => '2026-03-15',
                'lines' => [[
                    'previous_line_id' => 'invoice-line-1',
                    'line_no' => 1,
                    'product_id' => 'product-1',
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 100000,
                ]],
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['supplier_invoice']);
    }

    public function test_admin_cannot_receive_voided_supplier_invoice(): void
    {
        $this->seedVoidedInvoice();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.receive', ['supplierInvoiceId' => 'invoice-1']), [
                'tanggal_terima' => '2026-03-16',
                'lines' => [[
                    'supplier_invoice_line_id' => 'invoice-line-1',
                    'qty_diterima' => 1,
                ]],
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['supplier_receipt']);
    }

    public function test_admin_cannot_record_payment_for_voided_supplier_invoice(): void
    {
        $this->seedVoidedInvoice();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
                'payment_date' => '2026-03-16',
                'amount' => 10000,
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['supplier_payment']);
    }

    public function test_voided_supplier_invoice_detail_summary_marks_policy_state_voided(): void
    {
        $this->seedVoidedInvoice();

        $row = DB::table('supplier_invoices')
            ->where('id', 'invoice-1')
            ->first(['voided_at', 'void_reason']);

        self::assertNotNull($row);
        self::assertNotNull($row->voided_at);
        self::assertSame('Seeded as voided', $row->void_reason);
    }

    private function seedVoidedInvoice(): void
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
            'harga_jual' => 75000,
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
            'tanggal_pengiriman' => '2026-03-15',
            'jatuh_tempo' => '2026-04-15',
            'grand_total_rupiah' => 100000,
            'voided_at' => '2026-03-16 10:00:00',
            'void_reason' => 'Seeded as voided',
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
            'qty_pcs' => 2,
            'line_total_rupiah' => 100000,
            'unit_cost_rupiah' => 50000,
        ]);
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Void Guard',
            'email' => 'admin-void-guard-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
