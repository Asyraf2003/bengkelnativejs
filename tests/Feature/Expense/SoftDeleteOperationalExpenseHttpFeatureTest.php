<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SoftDeleteOperationalExpenseHttpFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_operational_expense(): void
    {
        $this->seedCategory('expense-category-1', 'EXP-ELEC', 'Listrik Bengkel');
        $this->seedExpense('expense-1', 'expense-category-1');

        $response = $this->actingAs($this->user('admin'))
            ->delete(route('admin.expenses.delete', ['expenseId' => 'expense-1']));

        $response->assertRedirect(route('admin.expenses.index'));
        $response->assertSessionHas('success', 'Pengeluaran operasional berhasil dihapus.');

        $this->assertDatabaseHas('operational_expenses', [
            'id' => 'expense-1',
        ]);

        $this->assertDatabaseMissing('operational_expenses', [
            'id' => 'expense-1',
            'deleted_at' => null,
        ]);
    }

    public function test_admin_gets_error_when_soft_deleting_missing_expense(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->delete(route('admin.expenses.delete', ['expenseId' => 'missing-expense']));

        $response->assertRedirect(route('admin.expenses.index'));
        $response->assertSessionHas('error', 'Pengeluaran operasional tidak ditemukan atau sudah dihapus.');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-expense-delete@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedCategory(string $id, string $code, string $name): void
    {
        DB::table('expense_categories')->insert([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedExpense(string $id, string $categoryId): void
    {
        DB::table('operational_expenses')->insert([
            'id' => $id,
            'category_id' => $categoryId,
            'category_code_snapshot' => 'EXP-ELEC',
            'category_name_snapshot' => 'Listrik Bengkel',
            'amount_rupiah' => 250000,
            'expense_date' => '2026-03-23',
            'description' => 'Bayar token listrik',
            'payment_method' => 'cash',
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
