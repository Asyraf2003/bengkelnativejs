<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExpenseTableDataAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_expense_table_data(): void
    {
        $this->get(route('admin.expenses.table'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_expense_table_data(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(route('admin.expenses.table'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_get_expense_table_json(): void
    {
        $this->seedCategory('expense-category-1', 'EXP-ELEC', 'Listrik Bengkel');
        $this->seedExpense('expense-1', 'expense-category-1', 'EXP-ELEC', 'Listrik Bengkel', 250000, '2026-03-23', 'Bayar token listrik', 'cash');

        $response = $this->actingAs($this->user('admin'))->get(route('admin.expenses.table'));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.rows.0.category_name', 'Listrik Bengkel');
        $response->assertJsonPath('data.rows.0.amount_rupiah', 250000);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-expense-table-access@example.test',
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

    private function seedExpense(
        string $id,
        string $categoryId,
        string $categoryCodeSnapshot,
        string $categoryNameSnapshot,
        int $amount,
        string $date,
        string $description,
        string $paymentMethod,
    ): void {
        DB::table('operational_expenses')->insert([
            'id' => $id,
            'category_id' => $categoryId,
            'category_code_snapshot' => $categoryCodeSnapshot,
            'category_name_snapshot' => $categoryNameSnapshot,
            'amount_rupiah' => $amount,
            'expense_date' => $date,
            'description' => $description,
            'payment_method' => $paymentMethod,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
