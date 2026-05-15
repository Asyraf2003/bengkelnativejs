<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductStockAdjustmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_recording_stock_adjustment(): void
    {
        $this->post(route('admin.products.stock-adjustments.store', ['productId' => 'product-1']))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_recording_stock_adjustment(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->post(route('admin.products.stock-adjustments.store', ['productId' => 'product-1']));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_record_stock_adjustment_from_product_edit_page(): void
    {
        $this->seedProduct();
        $this->seedInventory();

        $response = $this
            ->from(route('admin.products.edit', ['productId' => 'product-1']))
            ->actingAs($this->user('admin'))
            ->post(route('admin.products.stock-adjustments.store', ['productId' => 'product-1']), [
                'adjusted_at' => '2026-03-20',
                'qty_issue' => 3,
                'reason' => 'Rusak rak display',
            ]);

        $response->assertRedirect(route('admin.products.edit', ['productId' => 'product-1']));
        $response->assertSessionHas('success', 'Stock adjustment berhasil dicatat.');

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'stock_adjustment',
            'tanggal_mutasi' => '2026-03-20',
            'qty_delta' => -3,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => -30000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 4,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 40000,
        ]);

        $context = (string) DB::table('audit_logs')
            ->where('event', 'stock_adjustment_recorded')
            ->value('context');

        $this->assertStringContainsString('Rusak rak display', $context);
        $this->assertStringContainsString('product-1', $context);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $role . '-stock-adjustment@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedProduct(): void
    {
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);
    }

    private function seedInventory(): void
    {
        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 7,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 70000,
        ]);
    }
}
