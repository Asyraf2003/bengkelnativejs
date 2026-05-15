<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductIndexPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_product_index_page(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_product_index_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(route('admin.products.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_product_index_shell_page(): void
    {
        $response = $this->actingAs($this->user('admin'))->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSee('Tabel produk interaktif untuk admin');
        $response->assertSee('product-search-form', false);
        $response->assertSee('open-product-filter', false);
        $response->assertSee('product-table-body', false);
        $response->assertSee('admin-products-table.js');
    }

    private function user(string $role): User
    {
        $user = User::query()->create(['name' => 'Test', 'email' => $role.'@example.test', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => $role]);

        return $user;
    }
}
