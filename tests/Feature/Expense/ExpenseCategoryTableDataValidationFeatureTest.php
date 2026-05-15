<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExpenseCategoryTableDataValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_validation_error_when_is_active_filter_is_invalid(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('admin.expenses.categories.table', ['is_active' => 'yes']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-expense-category-table-validation@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
