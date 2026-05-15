<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExpenseTableDataValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_validation_error_when_date_range_is_invalid(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('admin.expenses.table', [
                'date_from' => '2026-03-25',
                'date_to' => '2026-03-20',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_from']);
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-expense-table-validation@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
