<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductTableDataQueryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_and_filter_product_table(): void
    {
        $this->seedProductRow('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000, 6);
        $this->seedProductRow('product-2', 'KB-002', 'Aki Kering', 'GS Astra', null, 120000, 3);
        $this->seedProductRow('product-3', 'KB-003', 'Ban Dalam', 'Federal', 80, 18000, 5);

        $response = $this->actingAs($this->admin())->get(route('admin.products.table', ['q' => 'Ban', 'merek' => 'Federal']));

        $response->assertOk();
        $response->assertJsonCount(2, 'data.rows');
        $response->assertJsonPath('data.meta.filters.q', 'Ban');
        $response->assertJsonPath('data.meta.filters.merek', 'Federal');
    }

    public function test_admin_can_sort_product_table_by_stok_saat_ini_desc(): void
    {
        $this->seedProductRow('product-1', 'KB-001', 'Ban Luar', 'Federal', 90, 35000, 2);
        $this->seedProductRow('product-2', 'KB-002', 'Aki Kering', 'GS Astra', null, 120000, 8);

        $response = $this->actingAs($this->admin())->get(route('admin.products.table', ['sort_by' => 'stok_saat_ini', 'sort_dir' => 'desc']));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.nama_barang', 'Aki Kering');
        $response->assertJsonPath('data.rows.1.nama_barang', 'Ban Luar');
    }

    public function test_admin_can_access_second_page_of_product_table(): void
    {
        for ($i = 1; $i <= 11; $i++) $this->seedProductRow('product-'.$i, 'KB-'.$i, 'Produk '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'Federal', null, 10000 + $i, 0);

        $response = $this->actingAs($this->admin())->get(route('admin.products.table', ['page' => 2]));

        $response->assertOk();
        $response->assertJsonPath('data.meta.page', 2);
        $response->assertJsonPath('data.meta.last_page', 2);
        $response->assertJsonPath('data.rows.0.nama_barang', 'Produk 11');
    }

    private function admin(): User
    {
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);
        return $user;
    }

    private function seedProductRow(string $id, ?string $kode, string $nama, string $merek, ?int $ukuran, int $harga, int $stok): void
    {
        DB::table('products')->insert(['id' => $id, 'kode_barang' => $kode, 'nama_barang' => $nama, 'merek' => $merek, 'ukuran' => $ukuran, 'harga_jual' => $harga]);
        DB::table('product_inventory')->insert(['product_id' => $id, 'qty_on_hand' => $stok]);
    }
}
