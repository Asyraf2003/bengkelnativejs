<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsProductLifecyclePayableHistoryMatrixFixture;
use Tests\TestCase;

final class ExtremeProductLifecycleWithPayableHistoryMatrixFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsProductLifecyclePayableHistoryMatrixFixture;

    public function test_admin_can_soft_delete_existing_product(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar');
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->delete(route('admin.products.delete', ['productId' => 'product-1']));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product berhasil dihapus.');

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'deleted_by_actor_id' => (string) $admin->getAuthIdentifier(),
        ]);

        $this->assertNotNull(DB::table('products')->where('id', 'product-1')->value('deleted_at'));
    }

    public function test_admin_gets_error_when_soft_deleting_missing_product(): void
    {
        $response = $this->actingAs($this->admin())
            ->delete(route('admin.products.delete', ['productId' => 'missing-product']));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error', 'Product tidak ditemukan atau sudah dihapus.');
    }

    public function test_soft_deleted_product_still_keeps_supplier_payable_row(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar');
        $this->seedSupplier('supplier-1', 'PT A');
        $this->seedInvoice('invoice-1', 'supplier-1', 'INV-1', 20000);
        $this->seedLine('line-1', 'invoice-1', 'product-1', 'KB-001', 'Ban Luar', 2, 20000, 10000);
        $this->seedPayment('payment-1', 'invoice-1', 5000);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.delete', ['productId' => 'product-1']))
            ->assertRedirect(route('admin.products.index'));

        $rows = $this->summary();

        $this->assertCount(1, $rows);
        $this->assertSame('invoice-1', $rows[0]['supplier_invoice_id']);
        $this->assertSame(15000, $rows[0]['outstanding_rupiah']);
    }

    public function test_soft_deleting_all_referenced_products_still_keeps_payable_summary(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar');
        $this->seedProduct('product-2', 'KB-002', 'Ban Dalam');
        $this->seedSupplier('supplier-1', 'PT A');

        $this->seedInvoice('invoice-1', 'supplier-1', 'INV-1', 20000);
        $this->seedInvoice('invoice-2', 'supplier-1', 'INV-2', 30000);

        $this->seedLine('line-1', 'invoice-1', 'product-1', 'KB-001', 'Ban Luar', 2, 20000, 10000);
        $this->seedLine('line-2', 'invoice-2', 'product-2', 'KB-002', 'Ban Dalam', 3, 30000, 10000);

        $this->actingAs($this->admin())->delete(route('admin.products.delete', ['productId' => 'product-1']));
        $this->actingAs($this->admin())->delete(route('admin.products.delete', ['productId' => 'product-2']));

        $rows = $this->summary();

        $this->assertCount(2, $rows);
        $this->assertSame('invoice-1', $rows[0]['supplier_invoice_id']);
        $this->assertSame('invoice-2', $rows[1]['supplier_invoice_id']);
    }

    public function test_soft_delete_does_not_damage_supplier_invoice_line_snapshot(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar');
        $this->seedSupplier('supplier-1', 'PT A');
        $this->seedInvoice('invoice-1', 'supplier-1', 'INV-1', 20000);
        $this->seedLine('line-1', 'invoice-1', 'product-1', 'KB-001', 'Ban Luar', 2, 20000, 10000);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.delete', ['productId' => 'product-1']))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('supplier_invoice_lines', [
            'id' => 'line-1',
            'product_id' => 'product-1',
            'product_kode_barang_snapshot' => 'KB-001',
            'product_nama_barang_snapshot' => 'Ban Luar',
            'qty_pcs' => 2,
            'line_total_rupiah' => 20000,
        ]);
    }

    public function test_soft_delete_does_not_remove_inventory_projection_costing_and_history(): void
    {
        $this->seedProduct('product-1', 'KB-001', 'Ban Luar');
        $this->seedInventoryState('product-1', 5, 10000, 50000);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.delete', ['productId' => 'product-1']))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 5,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'source_type' => 'seed_fixture',
            'qty_delta' => 5,
        ]);
    }

    private function admin(): User
    {
        $u = User::query()->create([
            'name' => 'Admin Product Lifecycle',
            'email' => 'admin-product-lifecycle-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $u->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $u;
    }
}
