<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExpenseIndexPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_expense_index_page(): void
    {
        $this->get(route('admin.expenses.index'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_expense_index_page(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(route('admin.expenses.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_expense_index_shell_page(): void
    {
        $this->seedExpenseCategory('expense-category-1', 'EXP-ELEC', 'Listrik Bengkel', true);
        $this->seedExpenseCategory('expense-category-2', 'EXP-MISC', 'Lain-lain', false);

        $response = $this->actingAs($this->user('admin'))->get(route('admin.expenses.index'));

        $response->assertOk();
        $response->assertSee('Daftar Pengeluaran Operasional');
        $response->assertSee('expense-search-form', false);
        $response->assertSee('expense-table-body', false);
        $response->assertSee('admin-expenses-table.js');
        $response->assertSee(json_encode(route('admin.expenses.table')), false);
        $response->assertSee('expense-delete-modal', false);
        $response->assertSee('expense-delete-form', false);
        $response->assertSee('Listrik Bengkel (EXP-ELEC)');
        $response->assertDontSee('Lain-lain (EXP-MISC)');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-expense-index@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedExpenseCategory(string $id, string $code, string $name, bool $isActive): void
    {
        DB::table('expense_categories')->insert([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => null,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
