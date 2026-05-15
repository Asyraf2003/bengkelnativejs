<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateExpenseCategoryPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_create_expense_category_page_from_expense_create_with_prefilled_keyword(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.expenses.categories.create', [
                'source' => 'expense_create',
                'q' => 'Biaya Karung',
            ]));

        $response->assertOk();
        $response->assertSee('Tambah Kategori Pengeluaran');
        $response->assertSee('Biaya Karung');
        $response->assertSee('expense_create');
        $response->assertSee(route('admin.expenses.create'), false);
        $response->assertSee('expense-category-create-form', false);
        $response->assertSee('admin-expense-category-create.js');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-expense-category-create@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
