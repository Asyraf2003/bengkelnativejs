<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierTableDataAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_supplier_table_data(): void
    {
        $this->get(route('admin.suppliers.table'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_supplier_table_data(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(route('admin.suppliers.table'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_get_supplier_table_json_with_summary_fields(): void
    {
        $this->seedSupplier('supplier-1', 'PT Federal Abadi');
        $this->seedInvoice('invoice-1', 'supplier-1', '2026-03-15', '2026-04-15', 100000);
        $this->seedPayment('payment-1', 'invoice-1', 40000, '2026-03-16', 'pending');
        $this->syncSupplierListProjectionForTest('supplier-1');

        $response = $this->actingAs($this->user('admin'))->get(route('admin.suppliers.table'));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.rows.0.nama_pt_pengirim', 'PT Federal Abadi');
        $response->assertJsonPath('data.rows.0.invoice_count', 1);
        $response->assertJsonPath('data.rows.0.outstanding_rupiah', 60000);
        $response->assertJsonPath('data.rows.0.invoice_unpaid_count', 1);
        $response->assertJsonPath('data.rows.0.last_shipment_date', '2026-03-15');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedSupplier(string $id, string $namaPtPengirim): void
    {
        DB::table('suppliers')->insert([
            'id' => $id,
            'nama_pt_pengirim' => $namaPtPengirim,
            'nama_pt_pengirim_normalized' => mb_strtolower($namaPtPengirim),
        ]);
    }

    private function seedInvoice(
        string $id,
        string $supplierId,
        string $shipmentDate,
        string $dueDate,
        int $grandTotal,
    ): void {
        DB::table('supplier_invoices')->insert([
            'id' => $id,
            'supplier_id' => $supplierId,
            'tanggal_pengiriman' => $shipmentDate,
            'jatuh_tempo' => $dueDate,
            'grand_total_rupiah' => $grandTotal,
        ]);
    }

    private function seedPayment(
        string $id,
        string $invoiceId,
        int $amount,
        string $paidAt,
        string $proofStatus,
    ): void {
        DB::table('supplier_payments')->insert([
            'id' => $id,
            'supplier_invoice_id' => $invoiceId,
            'amount_rupiah' => $amount,
            'paid_at' => $paidAt,
            'proof_status' => $proofStatus,
            'proof_storage_path' => null,
        ]);
    }
}
