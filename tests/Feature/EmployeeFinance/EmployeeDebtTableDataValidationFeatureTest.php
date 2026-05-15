<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EmployeeDebtTableDataValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_validation_error_when_sort_by_is_invalid(): void
    {
        $this->actingAs($this->admin())->getJson(route('admin.employee-debts.table', ['sort_by' => 'invalid']))->assertStatus(422)->assertJsonValidationErrors(['sort_by']);
    }

    public function test_admin_gets_validation_error_when_per_page_is_invalid(): void
    {
        $this->actingAs($this->admin())->getJson(route('admin.employee-debts.table', ['per_page' => 25]))->assertStatus(422)->assertJsonValidationErrors(['per_page']);
    }

    private function admin(): User
    {
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);
        return $user;
    }
}
