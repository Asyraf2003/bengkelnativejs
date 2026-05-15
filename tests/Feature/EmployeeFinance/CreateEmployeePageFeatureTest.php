<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateEmployeePageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_create_employee_page(): void
    {
        $this->get(route('admin.employees.create'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_create_employee_page(): void
    {
        $response = $this->actingAs($this->createUserWithRole('kasir-employee-create@example.test', 'kasir'))
            ->get(route('admin.employees.create'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_create_employee_page(): void
    {
        $response = $this->actingAs($this->createUserWithRole('admin-employee-create@example.test', 'admin'))
            ->get(route('admin.employees.create'));

        $response->assertOk();
        $response->assertSee('name="employee_name"', false);
        $response->assertSee('name="phone"', false);
        $response->assertSee('name="default_salary_amount"', false);
        $response->assertSee('name="salary_basis_type"', false);
        $response->assertSee('name="started_at"', false);
        $response->assertSee('name="ended_at"', false);
    }

    public function test_admin_can_store_employee_from_create_page(): void
    {
        $response = $this->actingAs($this->createUserWithRole('admin-employee-store@example.test', 'admin'))
            ->post(route('admin.employees.store'), [
                'employee_name' => 'Asyraf Mubarak',
                'phone' => '08111222333',
                'default_salary_amount' => 5000000,
                'salary_basis_type' => 'monthly',
                'started_at' => '2026-04-01',
                'ended_at' => null,
            ]);

        $response->assertRedirect(route('admin.employees.index'));
        $response->assertSessionHas('success', 'Data karyawan berhasil dibuat.');

        $this->assertDatabaseHas('employees', [
            'employee_name' => 'Asyraf Mubarak',
            'phone' => '08111222333',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'started_at' => '2026-04-01',
            'ended_at' => null,
        ]);
    }

    private function createUserWithRole(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
