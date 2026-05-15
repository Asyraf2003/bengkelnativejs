<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RestoreProductFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_restore_soft_deleted_product(): void
    {
        $this->seedDeletedProduct();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->patch(route('admin.products.restore', ['productId' => 'product-1']));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product berhasil direstore.');

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
        ]);
    }

    public function test_admin_gets_error_when_restoring_missing_product(): void
    {
        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->patch(route('admin.products.restore', ['productId' => 'missing-product']));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error', 'Product tidak ditemukan atau belum dihapus.');
    }

    public function test_admin_gets_error_when_restoring_active_product(): void
    {
        $this->seedActiveProduct();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.index'))
            ->patch(route('admin.products.restore', ['productId' => 'product-1']));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error', 'Product tidak ditemukan atau belum dihapus.');
    }

    private function seedDeletedProduct(): void
    {
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 75000,
            'deleted_at' => '2026-03-16 10:00:00',
            'deleted_by_actor_id' => 'admin-1',
            'delete_reason' => null,
        ]);
    }

    private function seedActiveProduct(): void
    {
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 75000,
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Restore Product',
            'email' => 'admin-restore-product-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
