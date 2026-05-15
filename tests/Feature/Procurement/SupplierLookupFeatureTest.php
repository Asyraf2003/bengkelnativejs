<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierLookupFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_supplier_lookup(): void
    {
        $this->get(route('admin.procurement.suppliers.lookup', ['q' => 'PT']))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_supplier_lookup(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.procurement.suppliers.lookup', ['q' => 'PT']));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_gets_empty_rows_when_query_is_shorter_than_two_characters(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->getJson(route('admin.procurement.suppliers.lookup', ['q' => 'P']));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(0, 'data.rows');
    }

    public function test_admin_can_lookup_supplier_by_nama_pt_pengirim(): void
    {
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur', 'pt sumber makmur');
        $this->seedSupplier('supplier-2', 'CV Bintang Jaya', 'cv bintang jaya');

        $response = $this->actingAs($this->user('admin'))
            ->getJson(route('admin.procurement.suppliers.lookup', ['q' => 'Sumber']));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.rows.0.id', 'supplier-1');
        $response->assertJsonPath('data.rows.0.nama_pt_pengirim', 'PT Sumber Makmur');
        $response->assertJsonPath('data.rows.0.label', 'PT Sumber Makmur');
    }

    public function test_admin_can_lookup_supplier_by_normalized_name(): void
    {
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur', 'pt sumber makmur');
        $this->seedSupplier('supplier-2', 'CV Bintang Jaya', 'cv bintang jaya');

        $response = $this->actingAs($this->user('admin'))
            ->getJson(route('admin.procurement.suppliers.lookup', ['q' => '  pt   sumber   makmur  ']));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.id', 'supplier-1');
        $response->assertJsonPath('data.rows.0.nama_pt_pengirim', 'PT Sumber Makmur');
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

    private function seedSupplier(
        string $id,
        string $namaPtPengirim,
        string $namaPtPengirimNormalized
    ): void {
        DB::table('suppliers')->insert([
            'id' => $id,
            'nama_pt_pengirim' => $namaPtPengirim,
            'nama_pt_pengirim_normalized' => $namaPtPengirimNormalized,
        ]);
    }
}
