<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExpenseCategoryEditPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_edit_expense_category_page(): void
    {
        $this->get(route('admin.expenses.categories.edit', ['categoryId' => 'cat-1']))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_when_accessing_edit_expense_category_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->get(route('admin.expenses.categories.edit', ['categoryId' => 'cat-1']));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_is_redirected_to_index_when_expense_category_edit_page_is_missing(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.expenses.categories.edit', ['categoryId' => 'missing-cat']));

        $response->assertRedirect(route('admin.expenses.categories.index'));
        $response->assertSessionHas('error', 'Expense category tidak ditemukan.');
    }

    public function test_admin_can_access_edit_expense_category_page(): void
    {
        $this->seedCategory('cat-1', 'EXP-ELEC', 'Listrik', true, 'Utilitas listrik');

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.expenses.categories.edit', ['categoryId' => 'cat-1']));

        $response->assertOk();
        $response->assertSee('Edit Kategori Pengeluaran');
        $response->assertSee('EXP-ELEC');
        $response->assertSee('Listrik');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $role . '-expense-category-edit@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedCategory(string $id, string $code, string $name, bool $isActive, ?string $description): void
    {
        DB::table('expense_categories')->insert([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
