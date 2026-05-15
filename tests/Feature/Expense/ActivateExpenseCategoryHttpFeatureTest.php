<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ActivateExpenseCategoryHttpFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_activate_expense_category_from_index_page(): void
    {
        $this->seedCategory('cat-1', false);

        $response = $this->actingAs($this->user('admin'))
            ->patch(route('admin.expenses.categories.activate', ['categoryId' => 'cat-1']));

        $response->assertRedirect(route('admin.expenses.categories.index'));
        $response->assertSessionHas('success', 'Expense category diaktifkan.');

        $this->assertDatabaseHas('expense_categories', ['id' => 'cat-1', 'is_active' => 1]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $role . '-expense-category-activate@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedCategory(string $id, bool $isActive): void
    {
        DB::table('expense_categories')->insert([
            'id' => $id,
            'code' => 'EXP-ELEC',
            'name' => 'Listrik',
            'description' => null,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
