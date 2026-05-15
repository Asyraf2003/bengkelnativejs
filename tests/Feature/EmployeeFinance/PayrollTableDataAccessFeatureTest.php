<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PayrollTableDataAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_payroll_table_data(): void
    {
        $this->get(route('admin.payrolls.table'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_payroll_table_data(): void
    {
        $response = $this->actingAs($this->user('kasir'))->get(route('admin.payrolls.table'));
        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_get_payroll_table_json(): void
    {
        DB::table('employees')->insert([
            'id' => '11111111-1111-1111-1111-111111111111',
            'employee_name' => 'Budi',
            'phone' => '0812',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'weekly',
            'employment_status' => 'active',
        ]);

        DB::table('payroll_disbursements')->insert([
            'id' => '22222222-2222-2222-2222-222222222222',
            'employee_id' => '11111111-1111-1111-1111-111111111111',
            'amount' => 5000000,
            'disbursement_date' => '2026-03-25 00:00:00',
            'mode' => 'monthly',
            'notes' => 'Gaji Maret',
        ]);

        $response = $this->actingAs($this->user('admin'))->get(route('admin.payrolls.table'));
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.rows.0.employee_name', 'Budi');
        $response->assertJsonPath('data.rows.0.mode_value', 'monthly');
    }

    private function user(string $role): User
    {
        $user = User::query()->create(['name' => 'Test', 'email' => $role.'@example.test', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => $role]);
        return $user;
    }
}
