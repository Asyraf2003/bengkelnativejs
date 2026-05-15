<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsEditableProcurementHeaderPolicyMatrixFixture;
use Tests\TestCase;

final class ExtremeEditableProcurementHeaderPolicyMatrixFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsEditableProcurementHeaderPolicyMatrixFixture;

    public function test_admin_can_revise_editable_invoice_with_non_empty_weird_reason(): void
    {
        $this->seedEditableInvoiceBase();

        $response = $this->actingAs($this->admin())
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), $this->payload([
                'change_reason' => '??? typo user x_x tapi tetap valid audit',
                'nomor_faktur' => 'INV-SUP-001-REV',
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHas('success', 'Nota supplier berhasil diperbarui.');

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => 'invoice-1',
            'nomor_faktur' => 'INV-SUP-001-REV',
            'last_revision_no' => 2,
        ]);
    }

    public function test_admin_cannot_revise_editable_invoice_without_reason(): void
    {
        $this->seedEditableInvoiceBase();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), $this->payload([
                'change_reason' => '',
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['change_reason']);
    }

    public function test_admin_cannot_revise_editable_invoice_with_whitespace_only_reason(): void
    {
        $this->seedEditableInvoiceBase();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), $this->payload([
                'change_reason' => '   ',
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['change_reason']);
    }

    public function test_admin_cannot_revise_editable_invoice_with_invalid_shipment_date(): void
    {
        $this->seedEditableInvoiceBase();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), $this->payload([
                'tanggal_pengiriman' => '2026-99-99',
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['tanggal_pengiriman']);
    }

    public function test_admin_cannot_revise_editable_invoice_with_expected_revision_zero(): void
    {
        $this->seedEditableInvoiceBase();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), $this->payload([
                'expected_revision_no' => 0,
            ]));

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['expected_revision_no']);
    }

    public function test_admin_cannot_revise_editable_invoice_with_empty_lines(): void
    {
        $this->seedEditableInvoiceBase();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), [
                'expected_revision_no' => 1,
                'change_reason' => 'Koreksi header matrix editable invoice.',
                'nomor_faktur' => 'INV-SUP-001',
                'nama_pt_pengirim' => 'PT Sumber Makmur',
                'tanggal_pengiriman' => '2026-03-15',
                'lines' => [],
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.edit', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors(['lines']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'expected_revision_no' => 1,
            'change_reason' => 'Koreksi header matrix editable invoice.',
            'nomor_faktur' => 'INV-SUP-001',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'tanggal_pengiriman' => '2026-03-15',
            'lines' => [[
                'previous_line_id' => 'invoice-1-line-1',
                'line_no' => 1,
                'product_id' => 'product-1',
                'qty_pcs' => 2,
                'line_total_rupiah' => 20000,
            ]],
        ], $overrides);
    }

    private function admin(): User
    {
        $u = User::query()->create([
            'name' => 'Admin Editable Header Matrix',
            'email' => 'admin-editable-header-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $u->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $u;
    }
}
