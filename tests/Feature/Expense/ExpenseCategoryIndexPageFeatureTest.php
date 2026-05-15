<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExpenseCategoryIndexPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_expense_category_index_page(): void
    {
        $this->get(route('admin.expenses.categories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_expense_category_index_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.expenses.categories.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_expense_category_index_shell_page(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.expenses.categories.index'));

        $response->assertOk();
        $response->assertSee('Kategori biaya operasional yang dipakai saat pencatatan operasional');
        $response->assertSee('expense-category-search-form', false);
        $response->assertSee('expense-category-table-body', false);
        $response->assertSee('admin-expense-categories-table.js');
        $response->assertSee(json_encode(route('admin.expenses.categories.table')), false);
        $response->assertSee('Tambah Kategori');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-expense-category-index@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
