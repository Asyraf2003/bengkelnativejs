<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductTableDataAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_product_table_data(): void
    {
        $this->get(route('admin.products.table'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_product_table_data(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(route('admin.products.table'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_get_product_table_json_with_inventory_projection(): void
    {
        DB::table('products')->insert([
            'id' => 'product-1', 'kode_barang' => 'KB-001', 'nama_barang' => 'Ban Luar',
            'merek' => 'Federal', 'ukuran' => 90, 'harga_jual' => 35000,
        ]);
        DB::table('product_inventory')->insert(['product_id' => 'product-1', 'qty_on_hand' => 6]);

        $response = $this->actingAs($this->user('admin'))->get(route('admin.products.table'));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.rows.0.nama_barang', 'Ban Luar');
        $response->assertJsonPath('data.rows.0.stok_saat_ini', 6);
    }

    private function user(string $role): User
    {
        $user = User::query()->create(['name' => 'Test', 'email' => $role.'@example.test', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => $role]);
        return $user;
    }
}
