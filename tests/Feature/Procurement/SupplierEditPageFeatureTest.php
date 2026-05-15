<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SupplierEditPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_edit_supplier_page(): void
    {
        $this->get(route('admin.suppliers.edit', ['supplierId' => 'supplier-1']))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_when_accessing_edit_supplier_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(route('admin.suppliers.edit', ['supplierId' => 'supplier-1']));
        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_is_redirected_to_index_when_supplier_edit_page_is_missing(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(route('admin.suppliers.edit', ['supplierId' => 'missing-supplier']));
        $response->assertRedirect(route('admin.suppliers.index'));
        $response->assertSessionHas('error', 'Supplier tidak ditemukan.');
    }

    public function test_admin_can_access_edit_supplier_page(): void
    {
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur');

        $response = $this->actingAs($this->user('admin'))->get(route('admin.suppliers.edit', ['supplierId' => 'supplier-1']));

        $response->assertOk();
        $response->assertSee('Edit Supplier');
        $response->assertSee('PT Sumber Makmur');
    }

    public function test_admin_can_update_supplier_from_edit_page(): void
    {
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur');

        $response = $this->actingAs($this->user('admin'))->put(route('admin.suppliers.update', ['supplierId' => 'supplier-1']), [
            'nama_pt_pengirim' => 'PT Supplier Baru',
        ]);

        $response->assertRedirect(route('admin.suppliers.index'));
        $response->assertSessionHas('success', 'Supplier berhasil diperbarui.');

        $this->assertDatabaseHas('suppliers', [
            'id' => 'supplier-1',
            'nama_pt_pengirim' => 'PT Supplier Baru',
            'nama_pt_pengirim_normalized' => 'pt supplier baru',
        ]);
    }

    public function test_admin_update_supplier_returns_back_with_error_when_duplicate_happens(): void
    {
        $this->seedSupplier('supplier-1', 'PT Sumber Makmur');
        $this->seedSupplier('supplier-2', 'PT Supplier Test');

        $response = $this->from(route('admin.suppliers.edit', ['supplierId' => 'supplier-2']))
            ->actingAs($this->user('admin'))
            ->put(route('admin.suppliers.update', ['supplierId' => 'supplier-2']), [
                'nama_pt_pengirim' => '  pt   sumber   makmur ',
            ]);

        $response->assertRedirect(route('admin.suppliers.edit', ['supplierId' => 'supplier-2']));
        $response->assertSessionHasErrors([
            'supplier' => 'Nama supplier sudah digunakan.',
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $role . '-supplier-edit@example.test',
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
}
