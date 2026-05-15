<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class StoreExpenseHttpFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_operational_expense_from_create_page(): void
    {
        $this->seedExpenseCategory('expense-category-1', 'EXP-ELEC', 'Listrik Bengkel', true, 'Token atau tagihan listrik');

        $response = $this->actingAs($this->user('admin'))
            ->post(route('admin.expenses.store'), [
                'category_id' => 'expense-category-1',
                'amount_rupiah' => 250000,
                'expense_date' => '2026-03-23',
                'description' => 'Bayar token listrik workshop',
                'payment_method' => 'cash',
            ]);

        $response->assertRedirect(route('admin.expenses.index'));
        $response->assertSessionHas('success', 'Pengeluaran operasional berhasil dicatat.');

        $this->assertDatabaseHas('operational_expenses', [
            'category_id' => 'expense-category-1',
            'category_code_snapshot' => 'EXP-ELEC',
            'category_name_snapshot' => 'Listrik Bengkel',
            'amount_rupiah' => 250000,
            'expense_date' => '2026-03-23',
            'description' => 'Bayar token listrik workshop',
            'payment_method' => 'cash',
            'deleted_at' => null,
        ]);
    }

    public function test_admin_store_operational_expense_returns_back_with_error_when_category_is_inactive(): void
    {
        $this->seedExpenseCategory('expense-category-1', 'EXP-ELEC', 'Listrik Bengkel', false, 'Token atau tagihan listrik');

        $response = $this->from(route('admin.expenses.create'))
            ->actingAs($this->user('admin'))
            ->post(route('admin.expenses.store'), [
                'category_id' => 'expense-category-1',
                'amount_rupiah' => 250000,
                'expense_date' => '2026-03-23',
                'description' => 'Bayar token listrik workshop',
                'payment_method' => 'cash',
            ]);

        $response->assertRedirect(route('admin.expenses.create'));
        $response->assertSessionHasErrors(['expense' => 'Expense category tidak aktif.']);
        $this->assertDatabaseCount('operational_expenses', 0);
    }

    public function test_admin_store_operational_expense_returns_back_with_validation_error_when_amount_is_invalid(): void
    {
        $this->seedExpenseCategory('expense-category-1', 'EXP-ELEC', 'Listrik Bengkel', true, 'Token atau tagihan listrik');

        $response = $this->from(route('admin.expenses.create'))
            ->actingAs($this->user('admin'))
            ->post(route('admin.expenses.store'), [
                'category_id' => 'expense-category-1',
                'amount_rupiah' => 0,
                'expense_date' => '2026-03-23',
                'description' => 'Bayar token listrik workshop',
                'payment_method' => 'cash',
            ]);

        $response->assertRedirect(route('admin.expenses.create'));
        $response->assertSessionHasErrors(['amount_rupiah']);
        $this->assertDatabaseCount('operational_expenses', 0);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-expense-store@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedExpenseCategory(string $id, string $code, string $name, bool $isActive, ?string $description): void
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
