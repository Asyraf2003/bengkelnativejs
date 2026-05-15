<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EmployeeIndexPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_employee_index_page(): void
    {
        $this->get(route('admin.employees.index'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_employee_index_page(): void
    {
        $response = $this->actingAs($this->createUser('kasir', 'kasir-employee-index@example.test'))->get(route('admin.employees.index'));
        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_employee_index_shell_page(): void
    {
        $response = $this->actingAs($this->createUser('admin', 'admin-employee-index@example.test'))->get(route('admin.employees.index'));
        $response->assertOk();
        $response->assertSee('employee-search-form', false);
        $response->assertSee('employee-table-body', false);
        $response->assertSee('admin-employees-table.js');
    }

    private function createUser(string $role, string $email): User
    {
        $user = User::query()->create(['name' => 'Test User', 'email' => $email, 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => $role]);
        return $user;
    }
}
