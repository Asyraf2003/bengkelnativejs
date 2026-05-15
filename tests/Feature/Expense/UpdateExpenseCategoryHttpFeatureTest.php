<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UpdateExpenseCategoryHttpFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_expense_category_from_edit_page(): void
    {
        $this->seedCategory('cat-1', 'EXP-ELEC', 'Listrik', true, 'Lama');

        $response = $this->actingAs($this->user('admin'))
            ->put(route('admin.expenses.categories.update', ['categoryId' => 'cat-1']), [
                'code' => 'EXP-UTIL',
                'name' => 'Utilitas',
                'description' => 'Baru',
            ]);

        $response->assertRedirect(route('admin.expenses.categories.index'));
        $response->assertSessionHas('success', 'Expense category berhasil diperbarui.');

        $this->assertDatabaseHas('expense_categories', [
            'id' => 'cat-1',
            'code' => 'EXP-UTIL',
            'name' => 'Utilitas',
            'description' => 'Baru',
        ]);
    }

    public function test_admin_update_expense_category_returns_back_with_error_when_duplicate_happens(): void
    {
        $this->seedCategory('cat-1', 'EXP-ELEC', 'Listrik', true, null);
        $this->seedCategory('cat-2', 'EXP-WIFI', 'Wifi', true, null);

        $response = $this->from(route('admin.expenses.categories.edit', ['categoryId' => 'cat-2']))
            ->actingAs($this->user('admin'))
            ->put(route('admin.expenses.categories.update', ['categoryId' => 'cat-2']), [
                'code' => 'EXP-ELEC',
                'name' => 'Wifi Baru',
                'description' => null,
            ]);

        $response->assertRedirect(route('admin.expenses.categories.edit', ['categoryId' => 'cat-2']));
        $response->assertSessionHasErrors([
            'expense_category' => 'Kode expense category sudah dipakai.',
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $role . '-expense-category-update@example.test',
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
