<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExpenseCategoryTableDataQueryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_and_filter_expense_category_table(): void
    {
        $this->seedCategory('cat-1', 'EXP-ELEC', 'Listrik', true, 'Utilitas listrik');
        $this->seedCategory('cat-2', 'EXP-WIFI', 'Wifi', false, 'Internet');

        $response = $this->actingAs($this->admin())->get(route('admin.expenses.categories.table', [
            'q' => 'Li',
            'is_active' => '1',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data.rows');
        $response->assertJsonPath('data.rows.0.code', 'EXP-ELEC');
        $response->assertJsonPath('data.meta.filters.q', 'Li');
        $response->assertJsonPath('data.meta.filters.is_active', 1);
    }

    public function test_admin_can_sort_expense_category_table_by_code_desc(): void
    {
        $this->seedCategory('cat-1', 'EXP-ELEC', 'Listrik', true, null);
        $this->seedCategory('cat-2', 'EXP-WIFI', 'Wifi', true, null);

        $response = $this->actingAs($this->admin())->get(route('admin.expenses.categories.table', [
            'sort_by' => 'code',
            'sort_dir' => 'desc',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.rows.0.code', 'EXP-WIFI');
        $response->assertJsonPath('data.rows.1.code', 'EXP-ELEC');
    }

    public function test_admin_can_access_second_page_of_expense_category_table(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->seedCategory(
                'cat-' . $i,
                'EXP-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'Kategori ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                true,
                null,
            );
        }

        $response = $this->actingAs($this->admin())->get(route('admin.expenses.categories.table', ['page' => 2]));

        $response->assertOk();
        $response->assertJsonPath('data.meta.page', 2);
        $response->assertJsonPath('data.meta.last_page', 2);
        $response->assertJsonCount(1, 'data.rows');
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-expense-category-table-query@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
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
