<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class StoreExpenseCategoryHttpFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_expense_category_and_return_to_expense_create_when_requested(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->post(route('admin.expenses.categories.store'), [
                'code' => 'EXP-BARU',
                'name' => 'Biaya Baru',
                'description' => 'Dibuat dari form pengeluaran',
                'source' => 'expense_create',
            ]);

        $categoryId = (string) DB::table('expense_categories')
            ->where('code', 'EXP-BARU')
            ->value('id');

        $response->assertRedirect(route('admin.expenses.create', ['category_id' => $categoryId]));
        $response->assertSessionHas('success', 'Kategori pengeluaran berhasil dibuat.');

        $this->assertDatabaseHas('expense_categories', [
            'code' => 'EXP-BARU',
            'name' => 'Biaya Baru',
            'is_active' => true,
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-expense-category-store@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
