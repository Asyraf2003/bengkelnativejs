<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class ProcurementInvoiceVoidedTableFilterFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_admin_can_filter_voided_procurement_invoice_rows(): void
    {
        $this->seedMinimalSupplier('supplier-1', 'PT Sumber Makmur', 'pt sumber makmur');

        DB::table('supplier_invoices')->insert([
            [
                'id' => 'invoice-active',
                'supplier_id' => 'supplier-1',
                'supplier_nama_pt_pengirim_snapshot' => 'PT Sumber Makmur',
                'nomor_faktur' => 'INV-ACTIVE-001',
                'nomor_faktur_normalized' => 'inv-active-001',
                'document_kind' => 'invoice',
                'lifecycle_status' => 'active',
                'origin_supplier_invoice_id' => null,
                'superseded_by_supplier_invoice_id' => null,
                'tanggal_pengiriman' => '2026-03-15',
                'jatuh_tempo' => '2026-04-15',
                'grand_total_rupiah' => 100000,
                'voided_at' => null,
                'void_reason' => null,
                'last_revision_no' => 1,
            ],
            [
                'id' => 'invoice-voided',
                'supplier_id' => 'supplier-1',
                'supplier_nama_pt_pengirim_snapshot' => 'PT Sumber Makmur',
                'nomor_faktur' => 'INV-VOID-001',
                'nomor_faktur_normalized' => 'inv-void-001',
                'document_kind' => 'invoice',
                'lifecycle_status' => 'active',
                'origin_supplier_invoice_id' => null,
                'superseded_by_supplier_invoice_id' => null,
                'tanggal_pengiriman' => '2026-03-16',
                'jatuh_tempo' => '2026-04-16',
                'grand_total_rupiah' => 120000,
                'voided_at' => '2026-03-17 10:00:00',
                'void_reason' => 'Salah input sebelum ada efek domain.',
                'last_revision_no' => 1,
            ],
        ]);

        $this->syncSupplierInvoiceProjectionForTest('invoice-active');
        $this->syncSupplierInvoiceProjectionForTest('invoice-voided');

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.procurement.supplier-invoices.table', ['payment_status' => 'voided']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.supplier_invoice_id', 'invoice-voided');
        $response->assertJsonPath('data.rows.0.policy_state', 'voided');
        $response->assertJsonPath('data.rows.0.payment_action_enabled', false);
        $response->assertJsonPath('data.rows.0.void_action_enabled', false);
        $response->assertJsonPath('data.rows.0.edit_action_url', '');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-voided-filter@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
