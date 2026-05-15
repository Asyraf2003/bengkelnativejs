<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductDetailPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_detail_product_page_and_see_threshold_information(): void
    {
        $user = $this->createUserWithRole('admin-product-detail@example.test', 'admin');

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
            'reorder_point_qty' => 10,
            'critical_threshold_qty' => 3,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('admin.products.show', ['productId' => 'product-1']));

        $response->assertOk();
        $response->assertSee('Detail Produk');
        $response->assertSee('Riwayat Versi Produk');
        $response->assertSee('Mulai Perlu Restok (Reorder Point)');
        $response->assertSee('Batas Stok Kritis');
        $response->assertSee('10');
        $response->assertSee('3');
    }

    private function createUserWithRole(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
