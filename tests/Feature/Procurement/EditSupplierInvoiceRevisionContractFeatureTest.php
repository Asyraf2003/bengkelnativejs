<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EditSupplierInvoiceRevisionContractFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_edit_page_even_when_invoice_has_recorded_payment(): void
    {
        $this->seedInvoice();
        $this->seedPayment();

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.revise', [
                'supplierInvoiceId' => 'invoice-1',
            ]));

        $response->assertOk();
        $response->assertSee('name="expected_revision_no"', false);
        $response->assertSee('name="change_reason"', false);
        $response->assertSee('name="lines[0][previous_line_id]"', false);
    }

    public function test_update_supplier_invoice_requires_revision_contract_fields(): void
    {
        $this->seedInvoice();

        $response = $this->actingAs($this->user('admin'))
            ->from(route('admin.procurement.supplier-invoices.revise', [
                'supplierInvoiceId' => 'invoice-1',
            ]))
            ->put(route('admin.procurement.supplier-invoices.update', [
                'supplierInvoiceId' => 'invoice-1',
            ]), [
                'nomor_faktur' => 'INV-SUP-001',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-15',
                'lines' => [
                    [
                        'previous_line_id' => 'invoice-line-1',
                        'line_no' => 1,
                        'product_id' => 'product-1',
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 20000,
                    ],
                ],
            ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.procurement.supplier-invoices.revise', [
            'supplierInvoiceId' => 'invoice-1',
        ]));
        $response->assertSessionHasErrors([
            'expected_revision_no',
            'change_reason',
        ]);
    }

    private function seedInvoice(): void
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
            'email' => $role . '-procurement-revision-contract@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
